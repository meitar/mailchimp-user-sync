<?php

namespace MailChimp\Sync\Admin;

class StatusIndicator {

	/**
	 * @var string $list_id The ID of the list to check against
	 */
	private $list_id;

	/**
	 * @var bool Boolean indicating whether all users are subscribed to the selected list
	 */
	public $status = false;

	/**
	 * @var int Percentage of users subscribed to list
	 */
	public $progress = 0;

	/**
	 * @var int Number of registered WP users
	 */
	public $user_count = 1;

	/**
	 * @var int Number of WP Users on the selected list (according to local meta value)
	 */
	public $subscriber_count = 0;

	/**
	 * @param $list_id
	 */
	public function __construct( $list_id ) {
		$this->list_id = $list_id;

		$this->user_count = $this->get_user_count();
		$this->subscriber_count = $this->get_subscriber_count();
		$this->status = ( $this->user_count === $this->subscriber_count );
		$this->progress = ceil( $this->subscriber_count / $this->user_count * 100 );
	}

	/**
	 * @return int
	 */
	private function get_user_count() {
		// count user meta rows WITHOUT meta field with key mailchimp_sync_{$LIST_ID}
		global $wpdb;

		// get number of users
		$user_count = $wpdb->get_var( "SELECT COUNT(u.ID) FROM {$wpdb->users} u" );

		return (int) $user_count;
	}

	/**
	 * @return int
	 */
	private function get_subscriber_count() {
		global $wpdb;

		// now get number of users with meta key
		$query = $wpdb->prepare( "SELECT COUNT(um.user_id) FROM {$wpdb->usermeta} um WHERE um.meta_key = %s", 'mailchimp_sync_' . $this->list_id );
		$subscriber_count = $wpdb->get_var( $query );

		return (int) $subscriber_count;
	}

}