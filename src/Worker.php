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
		$queue = $this->queue;
		$synchronizer = $this->synchronizer;

		// TODO: Prevent duplicate jobs
		add_action( 'user_register', function( $user_id ) use( $queue ) {
			$queue->put( array( 'type' => 'subscribe', 'user_id' => $user_id ) );
		});

		add_action( 'profile_update', function( $user_id ) use( $queue ) {
			$queue->put( array( 'type' => 'subscribe', 'user_id' => $user_id ) );
		});

		add_action( 'updated_user_meta', function( $meta_id, $user_id ) use( $queue ) {
			$queue->put( array( 'type' => 'subscribe', 'user_id' => $user_id ) );
		}, 10, 2 );

		add_action( 'delete_user', function( $user_id ) use( $queue, $synchronizer ) {
			// fetch meta value now, because user is about to be deleted
			$subscriber_uid = get_user_meta( $user_id, $synchronizer->meta_key, true );
			$queue->put( array( 'type' => 'unsubscribe', 'user_id' => $user_id, 'subscriber_uid' => $subscriber_uid ) );
		});

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