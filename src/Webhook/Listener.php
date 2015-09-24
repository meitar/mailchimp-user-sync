<?php

namespace MailChimp\Sync\Webhook;

use MailChimp\Sync\UserRepository;
use WP_User;

/**
 * Class Listener
 *
 * This class listens on your-site.com/mc4wp-api/webhook for MailChimp webhook events.
 *
 * Once triggered, it will look for the corresponding WP user and update it using the field map defined in the settings of the Sync plugin.
 *
 * @package MailChimp\Sync\Webhook
 * @property UserRepository $user_repository
 */
class Listener {

	/**
	 * @var
	 */
	public $options;

	/**
	 * @var string
	 */
	public $url = '/mc4wp-api/webhook';

	/**
	 * @param $options
	 */
	public function __construct( $options ) {
		$this->options = $options;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'listen' ) );
	}

	/**
	 * Listen for webhook requests
	 */
	public function listen() {
		if( $this->is_triggered() ) {
			$this->handle();
			exit;
		}
	}

	/**
	 * Yes?
	 *
	 * @return bool
	 */
	public function is_triggered() {
		return strpos( $_SERVER['REQUEST_URI'], $this->url ) !== false;
	}

	/**
	 * Handle the request
	 */
	public function handle() {

		$data = stripslashes_deep( $_REQUEST['data'] );
		$dirty = false;

		// do nothing if no "type" or "web_id" is given
		if( empty( $data['type'] ) || empty( $data['web_id'] ) ) {
			return false;
		}

		// find WP user by List_ID + MailChimp ID
		$user = $this->user_repository->get_user_by_mailchimp_id( $data['web_id'] );
		if( ! $user instanceof WP_User ) {
			return false;
		}

		// update user email if it's given, valid and different
		if( ! empty( $data['email'] ) && is_email( $data['email'] ) && $data['email'] !== $user->user_email ) {
			$user->user_email = $data['email'];
			$dirty = true;
		}

		// update WP user with data (use reversed field map)
		if( ! empty( $this->options['field_mappers'] ) ) {

			// loop through mapping rules
			foreach( $this->options['field_mappers'] as $rule ) {

				// is this field present in the request data?
				if( isset( $data['merges'][ $rule['mailchimp_field'] ] ) ) {

					// is scalar value?
					$value = $data['merges'][ $rule['mailchimp_field'] ];
					if( ! is_scalar( $value ) ) {
						continue;
					}

					// update user property if it changed
					if( $user->{$rule['user_field']} !== $value ) {
						$user->{$rule['user_field']} = $value;
						$dirty = true;
					}
				}

			}
		}

		// update user if something changed
		if( $dirty ) {
			wp_update_user( $user );
		}

		// fire event to allow custom actions (like deleting the user)
		do_action( 'mailchimp_sync_webhook', $data, $user );

		// fire type specific event. Example: mailchimp_sync_webhook_unsubscribe
		do_action( 'mailchimp_sync_webhook_' . $data['type'], $data, $user );
	}

	/**
	 * @param $var
	 *
	 * @return UserRepository|null
	 */
	public function __get( $var ) {

		switch( $var ) {
			case 'user_repository':
				return new UserRepository( $this->options['list'] );
			break;
		}

		return null;
	}

}