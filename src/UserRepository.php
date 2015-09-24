<?php

namespace MailChimp\Sync;

use WP_User;
use WP_User_Query;

class UserRepository {

	/**
	 * @param $list_id
	 */
	public function __construct( $list_id ) {
		$this->list_id = $list_id;
		$this->synchronizer = new ListSynchronizer( $list_id );
	}

	/**
	 * @param string $id
	 *
	 * @return WP_User|null;
	 */
	public function get_user_by_mailchimp_id( $id ) {

		$users = get_users(
			array(
				'meta_key'     => $this->synchronizer->meta_key,
				'meta_value'   => $id,
				'limit' => 1
			)
		);

		if( ! is_array( $users ) || empty( $users ) ) {
			return null;
		}

		return $users[0];
	}


}