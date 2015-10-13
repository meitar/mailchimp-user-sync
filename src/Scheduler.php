<?php

namespace MailChimp\Sync;

use WP_User;

class Scheduler {

	public function __construct() {}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		// hook into the various user related actions
		add_action( 'user_register', array( $this, 'schedule_subscribe' ) );
		add_action( 'profile_update', array( $this, 'schedule_update' ) );
		add_action( 'delete_user', array( $this, 'schedule_unsubscribe' ) );
	}

	/**
	 * @param string $event
	 * @param WP_User|int $user
	 */
	public function schedule( $event, $user ) {
		$event_name = ListSynchronizer::EVENT_PREFIX . $event;
		$args = array( $user );

		// we've already scheduled this
		if( wp_next_scheduled( $event_name, $args ) !== false ) {
			return;
		}

		wp_schedule_single_event( time() + 1, $event_name, $args );
	}

	/**
	 * @param $user_id
	 */
	public function schedule_subscribe( $user_id ) {
		$this->schedule( 'subscribe_user', $user_id );
	}

	/**
	 * @param $user_id
	 */
	public function schedule_update( $user_id ) {
		$this->schedule( 'update_subscriber', $user_id );
	}

	/**
	 * @param $user_id
	 */
	public function schedule_unsubscribe( $user_id ) {
		$this->schedule( 'unsubscribe_user', $user_id );
	}

}