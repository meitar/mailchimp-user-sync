<?php


namespace MC4WP\Sync;

use MC4WP_Queue as Queue;

class Worker {

	/**
	 * @var Queue
	 */
	private $queue;

	/**
	 * @var ListSynchronizer
	 */
	private $synchronizer;

	/**
	 * Worker constructor.
	 *
	 * @param Queue      $queue
	 * @param ListSynchronizer $synchronizer
	 */
	public function __construct( Queue $queue, ListSynchronizer $synchronizer ) {
		$this->queue = $queue;
		$this->synchronizer = $synchronizer;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		$synchronizer = $this->synchronizer;
		$worker = $this;

		add_action( 'user_register', function( $user_id ) use( $worker ) {
			$worker->schedule( array( 'type' => 'subscribe', 'user_id' => $user_id ) );
		});

		add_action( 'profile_update', function( $user_id ) use( $worker ) {
			$worker->schedule( array( 'type' => 'subscribe', 'user_id' => $user_id ) );
		});

		add_action( 'updated_user_meta', function( $meta_id, $user_id, $meta_key  ) use( $worker, $synchronizer ) {

			/*
			 * Don't act on our own actions or insignificant meta values
			 *
			 * @see https://wordpress.org/plugins/user-last-login/
			 * @see https://wordpress.org/plugins/wp-last-login/
			 */
			if( in_array( $meta_key, array( $synchronizer->meta_key, 'wp-last-login' ) ) ) {
				return;
			}

			$worker->schedule( array( 'type' => 'subscribe', 'user_id' => $user_id ) );
		}, 10, 3 );

		add_action( 'delete_user', function( $user_id ) use( $worker, $synchronizer ) {
			// fetch meta value now, because user is about to be deleted
			$subscriber_uid = get_user_meta( $user_id, $synchronizer->meta_key, true );
			$worker->schedule( array( 'type' => 'unsubscribe', 'user_id' => $user_id, 'subscriber_uid' => $subscriber_uid ) );
		});

	}

	/**
	 * Adds a task to the queue
	 *
	 * @param array $job_data
	 */
	public function schedule( $job_data ) {

		// Don't schedule anything when doing webhook
		if( defined( 'MC4WP_SYNC_DOING_WEBHOOK' ) && MC4WP_SYNC_DOING_WEBHOOK ) {
			return;
		}

		$this->queue->put( $job_data );
	}


	/**
	 * Put in work!
	 */
	public function work() {

		// We'll use this to keep track of what we've done
		$done = array();

		while( ( $job = $this->queue->get() ) ) {

			// get type & then unset it because we're using the rest as method parameters
			$method = $job->data['type'] . '_user';
			unset( $job->data['type'] );

			// don't perform the same job more than once
			if( ! in_array( $job->data, $done ) ) {

				// do the actual work
				$success = call_user_func_array( array( $this->synchronizer, $method ), $job->data );

				// keep track of what we've done
				$done[] = $job->data;
			}

			// remove job from queue
			$this->queue->delete( $job );
		}
	}
}