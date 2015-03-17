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
	 * @var array
	 */
	private $allowed_actions = array(
		'get_users',
		'subscribe_users'
	);

	/**
	 * Constructor
	 * @param array $options
	 */
	public function __construct( array $options ) {
		$this->options = $options;

		add_action( 'wp_ajax_mcs_wizard', array( $this, 'route' ) );
	}

	/**
	 * Route the AJAX call to the correct method
	 */
	public function route() {

		// make sure user is allowed to make the AJAX call
		if( ! current_user_can( 'manage_options' )
		    || ! isset( $_REQUEST['mcs_action'] ) ) {
			die( '-1' );
		}

		// check if method exists and is allowed
		if( in_array( $_REQUEST['mcs_action'], $this->allowed_actions ) ) {
			$this->{$_REQUEST['mcs_action']}();
			exit;
		}

		die( '-1' );
	}

	/**
	 * Responds with an array of all user ID's
	 */
	private function get_users() {
		global $wpdb;

		// query users in database, but only users with a valid email
		$sql = "SELECT ID, user_login AS username, user_email AS email
			FROM {$wpdb->users}
			WHERE user_email != ''";
		$result = $wpdb->get_results( $sql, OBJECT );

		// send response
		$this->respond( $result );
	}

	/**
	 * Subscribes the provided user ID's
	 * Returns the updates progress
	 */
	private function subscribe_users() {

		// instantiate list syncer for selected list
		$syncer = new ListSynchronizer( $this->options['list'], $this->options );

		// make sure `user_ids` is an array
		$user_ids = $_GET['user_ids'];
		if( ! is_array( $user_ids ) ) {
			$user_ids = sanitize_text_field( $user_ids );
			$user_ids = explode( ',', $user_ids );
		}

		// loop through user ID's
		foreach( $user_ids as $user_id ) {
			$result = $syncer->update_subscriber( $user_id );
		}

		if( $result ) {
			$this->respond( array( 'success' => true ) );
			exit;
		}

		// get api error
		$api = mc4wp_get_api();
		$error = $api->get_error_message();

		// send response
		$this->respond(
			array(
				'success' => $result,
				'error' => $error
			)
		);
		exit;
	}

	/**
	 * Send a JSON response
	 *
	 * @param $data
	 */
	private function respond( $data ) {

		// clear output, some plugins might have thrown errors by now.
		if( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		wp_send_json( $data );
		exit;
	}

}