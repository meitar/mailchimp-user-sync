<?php

namespace MC4WP\Sync;

use Exception;
use MC4WP_API;
use MC4WP_MailChimp;
use MC4WP_MailChimp_Subscriber_Data;
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
	 * @param Users $users
	 * @param array  $settings
	 */
	public function __construct( $list_id, Users $users, array $settings = array() ) {

		$this->list_id = $list_id;
		$this->users = $users;

		// if settings were passed, merge those with the defaults
		if( $settings ) {
			$this->settings = array_merge( $this->settings, $settings );
		}

		$this->log = $this->get_log();
	}

	/**
	 * Add hooks to call the subscribe, update & unsubscribe methods automatically
	 */
	public function add_hooks() {
		// custom actions for people to use if they want to call the class actions
		// @todo If we ever allow multiple instances of this class, these actions need the list_id property
		add_action( self::EVENT_PREFIX . 'subscribe_user', array( $this, 'subscribe_user' ) );
		add_action( self::EVENT_PREFIX . 'update_subscriber', array( $this, 'subscribe_user' ) );
		add_action( self::EVENT_PREFIX . 'unsubscribe_user', array( $this, 'unsubscribe_user' ) );
	}
	
	/**
	 * Handle
	 *
	 * @param int $user_id
	 * @return boolean
	 */
	public function handle_user( $user_id ) {

		try {
			$user = $this->users->user( $user_id );
		} catch( Exception $e ) {
			return false;
		}

		return $this->users->should( $user ) ? $this->subscribe_user( $user->ID ) : $this->unsubscribe_user( $user->ID );
	}

	/**
	 * Subscribes a user to the selected MailChimp list, stores a meta field with the subscriber uid
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function subscribe_user( $user_id ) {

		try {
			$user = $this->users->user( $user_id );
		} catch( Exception $e ) {
			$this->error = $e->getMessage();
			return false;
		}

		// if role is set, make sure user has that role
		if( ! $this->users->should( $user ) ) {
			$this->error = sprintf( 'Skipping user %d', $user->ID );
			return false;
		}

		// Only subscribe user if it has a valid email address
		if( '' === $user->user_email || ! is_email( $user->user_email ) ) {
			$this->error = 'Invalid email.';
			$this->log->warning( sprintf( 'User Sync > %s is an invalid email address', $user->user_email ) );
			return false;
		}

		$user_subscriber = $this->get_user_subscriber();
		$success = $user_subscriber->subscribe( $user->ID, $this->settings['double_optin'], $this->settings['email_type'], $this->settings['update_existing'], $this->settings['replace_interests'], $this->settings['send_welcome'] );

		// Error?
		if( ! $success ) {
			$this->error = $user_subscriber->error_message;
			$this->log->error( sprintf( 'User Sync > Error subscribing or updating user %d: %s', $user_id, $this->error ) );
			return false;
		}

		// Success!
		$this->log->info( sprintf( 'User Sync > Successfully subscribed or updated user %d', $user->ID ) );

		return true;
	}

	/**
	 * Delete the subscriber uid from the MailChimp list
	 *
	 * @param int $user_id
	 * @param string $subscriber_uid_or_email (optional)
	 *
	 * @return bool
	 */
	public function unsubscribe_user( $user_id, $subscriber_uid_or_email = '' ) {
		$user_subscriber = $this->get_user_subscriber();
		$success = $user_subscriber->unsubscribe( $user_id, $subscriber_uid_or_email, $this->settings['send_goodbye'], $this->settings['send_notification'], $this->settings['delete_member'] );

		// Error?
		if( ! $success ) {
			$this->error = $user_subscriber->error_message;
			$this->log->error( sprintf( 'User Sync > Error unsubscribing user %d: %s', $user_id, $this->error ) );
			return false;
		}

		$this->log->info( sprintf( 'User Sync > Successfully unsubscribed user %d', $user_id ) );
		return true;
	}

	/**
	 *
	 *
	 * @return UserSubscriber|UserSubscriberAPIv2
	 */
	private function get_user_subscriber() {
		if( ! class_exists( 'MC4WP_API_v3' ) ) {
			return new UserSubscriberAPIv2( $this->users, $this->list_id );
		}

		return new UserSubscriber( $this->users, $this->list_id );
	}

	/**
	 * Returns an instance of the Debug Log
	 *
	 * @return \MC4WP_Debug_Log
	 */
	private function get_log() {
		return mc4wp( 'log' );
	}

	
}


