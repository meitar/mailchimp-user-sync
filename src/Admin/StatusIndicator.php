<?php

namespace MailChimp\Sync\Admin;

class StatusIndicator {

	/**
	 * @var string $list_id
	 */
	private $list_id;

	/**
	 * @var bool
	 */
	public $status = false;

	/**
	 * @param $list_id
	 */
	public function __construct( $list_id ) {
		$this->list_id = $list_id;
		$this->status = $this->check();
	}

	/**
	 * Check if WP User base is in sync with MailChimp List
	 *
	 * @return bool
	 */
	private function check() {

		// count user meta rows WITHOUT meta field with key mailchimp_sync_{$LIST_ID}
		global $wpdb;

		// get number of users
		$user_count = $wpdb->get_var( "SELECT COUNT(u.ID) FROM {$wpdb->users} u" );

		// now get number of users with meta key
		$query = $wpdb->prepare( "SELECT COUNT(u.ID) FROM {$wpdb->users} u, {$wpdb->usermeta} um WHERE u.ID = um.user_id AND um.meta_key = %s", 'mailchimp_sync_' . $this->list_id );
		$synced_user_count = $wpdb->get_var( $query );

		return (int) $user_count === (int) $synced_user_count;
	}

}