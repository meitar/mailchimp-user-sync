<?php

namespace MailChimp\Sync\AJAX;

use MailChimp\Sync\Admin\StatusIndicator,
	MailChimp\Sync\ListSynchronizer;

class Wizard {

	/**
	 * @var array
	 */
	private $options;

	/**
	 * Constructor
	 * @param array $options
	 */
	public function __construct( array $options ) {
		$this->options = $options;

		add_action( 'wp_ajax_mailchimp_sync', array( $this, 'route' ) );
	}

	/**
	 * Route the AJAX call to the correct method
	 */
	public function route() {

		// make sure user is allowed to make the AJAX call
		if( ! current_user_can( 'manage_options' )
		    || ! isset( $_REQUEST['sync_action'] ) ) {
			die( '-1' );
		}

		// check if method exists and is allowed
		if( in_array( $_REQUEST['sync_action'], array( 'get_users', 'subscribe_users' ) ) ) {
			return $this->{$_REQUEST['sync_action']}();
		}

		die( '-1' );
	}

	/**
	 * Responds with an array of all user ID's
	 */
	private function get_users() {
		global $wpdb;

		// query users in database
		$result = $wpdb->get_results( "SELECT ID FROM {$wpdb->users}", OBJECT_K );
		$data = array_keys( $result );
		$this->respond( $data );
	}

	/**
	 * Subscribes the provided user ID's
	 * Returns the updates progress
	 */
	private function subscribe_users() {
		$syncer = new ListSynchronizer( $this->options['list'], $this->options );

		// loop through user ID's
		$user_ids = (array) $_REQUEST['user_ids'];

		foreach( $user_ids as $user_id ) {
			$syncer->update_subscriber( $user_id );
		}

		$status = new StatusIndicator( $this->options['list'] );

		// build data response
		$data = array(
			'progress' => $status->progress
		);

		// send response
		$this->respond( $data );
	}

	/**
	 * @param $data
	 */
	private function respond( $data ) {
		wp_send_json( $data );
		die();
	}

}