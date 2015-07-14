<?php

namespace MailChimp\Sync;

use WP_User;

class ListSynchronizer {


	/**
	 * @var string The List ID to sync with
	 */
	private $list_id;

	/**
	 * @var string
	 */
	private $user_role = '';
	/**
	 * @var string
	 */
	public $meta_key = 'mailchimp_sync';

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var array
	 */
	private $settings = array(
		'double_optin' => 0,
		'send_welcome' => 0,
		'update_existing' => 1,
		'replace_interests' => 0,
		'email_type' => 'html',
		'send_goodbye' => 0,
		'send_notification' => 0,
		'delete_member' => 0
	);

	/**
	 * Constructor
	 *
	 * @param string $list_id
	 * @param string $user_role
	 * @param array  $settings
	 */
	public function __construct( $list_id, $user_role = '', array $settings = null ) {

		$this->list_id = $list_id;
		$this->user_role = $user_role;

		// generate meta key name
		$this->meta_key = $this->meta_key . '_' . $this->list_id;

		// if settings were passed, merge those with the defaults
		if( $settings ) {
			$this->settings = array_merge( $this->settings, $settings );
		}
	}

	/**
	 * Add hooks to call the subscribe, update & unsubscribe methods automatically
	 */
	public function add_hooks() {
		// hook into the various user related actions
		add_action( 'user_register', array( $this, 'subscribe_user' ) );
		add_action( 'profile_update', array( $this, 'update_subscriber' ) );
		add_action( 'delete_user', array( $this, 'unsubscribe_user' ) );
	}

	/**
	 * Subscribes a user to the selected MailChimp list, stores a meta field with the subscriber uid
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function subscribe_user( $user_id ) {

		$user =  get_user_by( 'id', $user_id );

		// do nothing if user has no valid email
		if(  ! $user instanceof WP_User ) {
			$this->error = 'Invalid user ID.';
			return false;
		}

		if( '' === $user->user_email || ! is_email( $user->user_email ) ) {
			$this->error = 'Invalid email.';
			return false;
		}

		// if role is set, make sure user has that role
		if( '' !== $this->user_role && ! in_array( $this->user_role, $user->roles ) ) {
			return false;
		}

		$merge_vars = $this->extract_merge_vars_from_user( $user );

		// subscribe the user
		$api = mc4wp_get_api();
		$success = $api->subscribe( $this->list_id, $user->user_email, $merge_vars, $this->settings['email_type'], $this->settings['double_optin'], $this->settings['update_existing'], $this->settings['replace_interests'], $this->settings['send_welcome'] );

		if( $success ) {

			// get subscriber uid
			$subscriber_uid = $api->get_last_response()->leid;

			// store meta field with subscriber uid
			update_user_meta( $user_id, $this->meta_key, $subscriber_uid );
			return true;
		}

		// store error message returned by API
		$this->error = $api->get_error_message();
		error_log( sprintf( 'MailChimp Sync: Can not subscribe user %d. MailChimp returned the following error: %s', $user_id, $this->error ) );

		return false;
	}

	/**
	 * Delete the subscriber uid from the MailChimp list
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function unsubscribe_user( $user_id ) {

		// get subscriber uid from user meta
		$subscriber_uid = get_user_meta( $user_id, $this->meta_key, true );

		if( is_string( $subscriber_uid ) && '' !== $subscriber_uid ) {

			// unsubscribe user email from the selected list
			$api = mc4wp_get_api();
			$success = $api->unsubscribe( $this->list_id, array( 'leid' => $subscriber_uid ), $this->settings['send_goodbye'], $this->settings['send_notification'], $this->settings['delete_member'] );

			if( $success ) {
				// delete user meta
				delete_user_meta( $user_id, $this->meta_key );
				return true;
			}

		}

		return false;
	}

	/**
	 * Update the subscriber uid with the new user data
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function update_subscriber( $user_id ) {

		// get subscriber uid from user meta
		$subscriber_uid = get_user_meta( $user_id, $this->meta_key, true );

		// if subscriber uid is empty, add to list
		if( ! is_string( $subscriber_uid ) || $subscriber_uid === '' ) {
			return $this->subscribe_user( $user_id );
		}

		$user = get_user_by( 'id', $user_id );

		// do nothing if user has no valid email
		if( ! $user instanceof WP_User ) {
			$this->error = 'Invalid user ID.';
			return false;
		} elseif( '' === $user->user_email || ! is_email( $user->user_email ) ) {
			$this->error = 'Invalid email.';
			return false;
		}

		// if role is set, make sure user has that role
		if( '' !== $this->user_role && ! in_array( $this->user_role, $user->roles ) ) {
			return false;
		}

		$merge_vars = $this->extract_merge_vars_from_user( $user );
		$merge_vars['new-email'] = $user->user_email;

		// update subscriber in mailchimp
		$api = mc4wp_get_api();
		$success = $api->update_subscriber( $this->list_id, array( 'leid' => $subscriber_uid ), $merge_vars, $this->settings['email_type'], $this->settings['replace_interests'] );

		// TODO: Remove check for `get_error_code`, available since MailChimp for WP Lite 2.2.8 and MailChimp for WP Pro 2.6.3. Update dependency check in that case.
		if( ! $success ) {

			// subscriber leid did not match anything in the list, remove it and re-subscribe.
			if( ! method_exists( $api, 'get_error_code' ) || $api->get_error_code() === 232 ) {
				delete_user_meta( $user_id, $this->meta_key );
				return $this->subscribe_user( $user_id );
			}

			$this->error = $api->get_error_message();
			error_log( sprintf( 'MailChimp Sync: Can not update user %d. MailChimp returned the following error: %s', $user_id, $this->error ) );
		}

		return $success;
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return array
	 */
	private function extract_merge_vars_from_user( \WP_User $user ) {

		$data = array();

		if( '' !== $user->first_name ) {
			$data['FNAME'] = $user->first_name;
		}

		if( '' !== $user->last_name ) {
			$data['LNAME'] = $user->last_name;
		}

		if( '' !== $user->first_name  && '' !== $user->last_name ) {
			$data['NAME'] = sprintf( '%s %s', $user->first_name, $user->last_name );
		}

		// Allow other WP extensions to set other list fields (merge variables).
		$data = apply_filters( 'mailchimp_sync_user_data', $data, $user );

		return $data;
	}


}
