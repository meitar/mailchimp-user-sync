<?php

namespace MC4WP\Sync;

use Exception;
use MC4WP_API;
use WP_User;

class ListSynchronizer {

	/**
	 * @const string
	 */
	const EVENT_PREFIX = 'mailchimp_sync_';

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
		'delete_member' => 0,
		'field_mappers' => array()
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

		$this->tools = new Tools();
		$this->log = $this->get_log();
	}

	/**
	 * Add hooks to call the subscribe, update & unsubscribe methods automatically
	 */
	public function add_hooks() {
		// custom actions for people to use if they want to call the class actions
		// @todo If we ever allow multiple instances of this class, these actions need the list_id property
		add_action( self::EVENT_PREFIX . 'subscribe_user', array( $this, 'subscribe_user' ) );
		add_action( self::EVENT_PREFIX . 'update_subscriber', array( $this, 'update_subscriber' ) );
		add_action( self::EVENT_PREFIX . 'unsubscribe_user', array( $this, 'unsubscribe_user' ) );
	}

	/**
	 * @param mixed $user A user ID or a WP_User object
	 *
	 * @return bool|WP_User
	 */
	protected function get_user( $user ) {

		if( ! is_object( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		if( ! $user instanceof WP_User ) {
			$this->error = 'Invalid user ID.';
			return false;
		}

		return $user;
	}

	/**
	 * @param WP_User $user
	 *
	 * @return string
	 */
	public function get_user_subscriber_uid( WP_User $user ) {
		$subscriber_uid = get_user_meta( $user->ID, $this->meta_key, true );

		if( is_string( $subscriber_uid ) && '' !== $subscriber_uid ) {
			return $subscriber_uid;
		}

		return null;
	}

	/**
	 * @param WP_User $user
	 * @return bool
	 */
	public function should_sync_user( WP_User $user ) {

		$sync = true;

		// if role is set, make sure user has that role
		if( '' !== $this->user_role && ! in_array( $this->user_role, $user->roles ) ) {
			$sync = false;
		}

		/**
		 * Filters whether a user should be synchronized with MailChimp or not.
		 *
		 * @param boolean $sync
		 * @param WP_User $user
		 */
		return (bool) apply_filters( 'mailchimp_sync_should_sync_user', $sync, $user );
	}

	/**
	 * Subscribes a user to the selected MailChimp list, stores a meta field with the subscriber uid
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function subscribe_user( $user_id ) {

		$user = $this->get_user( $user_id );
		if( ! $user ) {
			return false;
		}

		// if role is set, make sure user has that role
		if( ! $this->should_sync_user( $user ) ) {
			return false;
		}

		// Only subscribe user if it has a valid email address
		if( '' === $user->user_email || ! is_email( $user->user_email ) ) {
			$this->error = 'Invalid email.';

			if( $this->log ) {
				$this->log->warning( sprintf( 'User Sync > %s is an invalid email address.', $user->user_email ) );
			}

			return false;
		}

		$api = $this->get_api();
		$merge_vars = $this->extract_merge_vars_from_user( $user );

		// subscribe the user
		$success = $api->subscribe( $this->list_id, $user->user_email, $merge_vars, $this->settings['email_type'], $this->settings['double_optin'], $this->settings['update_existing'], $this->settings['replace_interests'], $this->settings['send_welcome'] );

		// Error?
		if( ! $success ) {
			// store error message returned by API
			$this->error = $api->get_error_message();

			if( $this->log ) {
				$this->log->error( sprintf( 'User Sync > Error subscribing user %d: %s', $user_id, $this->error ) );
			}

			return false;
		}

		// Success!

		// get subscriber uid
		$subscriber_uid = $api->get_last_response()->leid;

		// store meta field with subscriber uid
		update_user_meta( $user_id, $this->meta_key, $subscriber_uid );

		if( $this->log ) {
			$this->log->info( sprintf( 'User Sync > Successfully subscribed user %d', $user->ID ) );
		}

		return true;
	}

	/**
	 * Delete the subscriber uid from the MailChimp list
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function unsubscribe_user( $user_id ) {

		// get subscriber uid from user meta
		$user = $this->get_user( $user_id );
		$subscriber_uid = $this->get_user_subscriber_uid( $user );

		// user isn't subscribed, simply return true then..
		if( empty( $subscriber_uid ) ) {
			return true;
		}

		// unsubscribe user email from the selected list
		$api = $this->get_api();
		$success = $api->unsubscribe( $this->list_id, array( 'leid' => $subscriber_uid ), $this->settings['send_goodbye'], $this->settings['send_notification'], $this->settings['delete_member'] );

		// Error?
		if( ! $success ) {

			if( $this->log ) {
				$this->log->error( sprintf( 'User Sync > Error unsubscribing user %d: %s', $user->ID, $api->get_error_message() ) );
			}

			return false;
		}

		// Success!
		delete_user_meta( $user_id, $this->meta_key );

		if( $this->log ) {
			$this->log->info( sprintf( 'User Sync > Successfully unsubscribed user %d', $user->ID ) );
		}

		return true;
	}

	/**
	 * Update the subscriber uid with the new user data
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function update_subscriber( $user_id ) {

		// get user
		$user = $this->get_user( $user_id );
		if( ! $user ) {
			return false;
		}

		// get subscriber uid
		$subscriber_uid = $this->get_user_subscriber_uid( $user );
		if( ! $subscriber_uid ) {
			return $this->subscribe_user( $user_id );
		}

		// check if user should be synced
		if( ! $this->should_sync_user( $user ) ) {
			return false;
		}

		// check email address
		if( '' === $user->user_email || ! is_email( $user->user_email ) ) {
			$this->error = 'Invalid email.';

			if( $this->log ) {
				$this->log->warning( sprintf( 'User Sync > %s is an invalid email address.', $user->user_email ) );
			}

			return false;
		}

		$merge_vars = $this->extract_merge_vars_from_user( $user );
		$merge_vars['new-email'] = $user->user_email;

		// TODO: Check if anything changed? If it's just login date, do nothing.

		// update subscriber in mailchimp
		$api = $this->get_api();
		$success = $api->update_subscriber( $this->list_id, array( 'leid' => $subscriber_uid ), $merge_vars, $this->settings['email_type'], $this->settings['replace_interests'] );

		// Error?
		if( ! $success ) {

			// subscriber leid did not match anything in the list
			if( $api->get_error_code() === 232 ) {

				// delete subscriber leid as it's apparently wrong
				delete_user_meta( $user_id, $this->meta_key );

				// re-subscribe user
				return $this->subscribe_user( $user_id );
			}

			// other errors
			$this->error = $api->get_error_message();

			if( $this->log ) {
				$this->log->error( sprintf( 'User Sync > Error updating user %d. %s', $user_id, $this->error ) );
			}

			return false;
		}

		// Success!

		if( $this->log ) {
			$this->log->info( sprintf( 'User Sync > Successfully updated user %d', $user->ID ) );
		}

		return true;
	}

	/**
	 * @param WP_User $user
	 *
	 * @return array
	 */
	protected function extract_merge_vars_from_user( WP_User $user ) {

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

		// Do we have mapping rules for user fields to mailchimp fields?
		if( ! empty( $this->settings['field_mappers'] ) ) {

			// loop through mapping rules
			foreach( $this->settings['field_mappers'] as $rule ) {

				// get field value
				$value = $this->tools->get_user_field( $user, $rule['user_field'] );
				if( is_string( $value ) ) {
					$data[ $rule['mailchimp_field'] ] = $value;
				}
			}
		}

		/**
		 * Filters the merge vars which are sent to MailChimp
		 *
		 * @param array $data The data that is sent.
		 * @param WP_User $user The user which is synchronized
		 */
		$data = (array) apply_filters( 'mailchimp_sync_user_data', $data, $user );

		return $data;
	}

	/**
	 * Returns an instance of the Debug Log or null (when running MailChimp for WP 3.0.x)
	 *
	 * @return \MC4WP_Debug_Log
	 */
	private function get_log() {

		try {
			$log = mc4wp( 'log' );
		} catch( Exception $e ) {
			$log = null;
		}

		return $log;
	}

	/**
	 * @return MC4WP_API
	 */
	private function get_api() {
		return mc4wp( 'api' );
	}

}


