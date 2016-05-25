<?php

namespace MC4WP\Sync\Admin;

use MC4WP\Sync\Users;

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
	public $user_count = 0;

	/**
	 * @var int Number of WP Users on the selected list (according to local meta value)
	 */
	public $subscriber_count = 0;

	/**
	 * @var string
	 */
	public $user_role = '';

	/**
	 * @param        $list_id
	 * @param string $user_role
	 */
	public function __construct( $list_id, $user_role = '' ) {
		$this->list_id   = $list_id;
		$this->user_role = $user_role;
		$this->users = new Users( 'mailchimp_sync_' . $list_id );
	}

	/**
	 *
	 */
	public function check() {
		$this->user_count = $this->users->count( $this->user_role );
		$this->subscriber_count = $this->users->count_subscribers( $this->user_role );
		$this->status = ( $this->user_count === $this->subscriber_count );
		$this->progress = ( $this->user_count > 0 ) ? ceil( $this->subscriber_count / $this->user_count * 100 ) : 0;
	}



}