<?php

namespace MC4WP\Sync;

use WP_User;
use WP_User_Query;
use Exception;

/**
 * Class UserRepository
 *
 * @package MC4WP\Sync
 * @property ListSynchronizer $synchronizer
 */
class Users {

	/**
	 * @var string
	 */
	private $meta_key = '';

	/**
	 * @var string
	 */
	private $role = '';

	/**
	 * @param string $meta_key
	 */
	public function __construct( $meta_key, $role = '' ) {
		$this->meta_key = $meta_key;
		$this->role = $role;
	}

	/**
	 * @param array $additional_args
	 *
	 * @return array
	 */
	public function get( $additional_args = array() ) {
		$args = array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => $this->meta_key,
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => $this->meta_key,
					'compare' => 'EXISTS'
				)
			),
			'fields' => array( 'ID', 'user_login', 'user_email' ),
			'orderby' => 'meta_value'
		);

		if( ! empty( $this->role ) ) {
			$args['role'] = $this->role;
		}

		$args = array_replace_recursive( $args, $additional_args );

		$user_query = new WP_User_Query( $args );

		return $user_query->get_results();
	}

	/**
	 *
	 * @return int
	 */
	public function count() {
		$count = count_users();

		if( ! empty( $role ) ) {
			return isset( $count['avail_roles'][ $this->role  ] ) ? $count['avail_roles'][ $this->role ] : 0;
		}

		return $count['total_users'];
	}

	/**
	 * @param string $id
	 *
	 * @return WP_User|null;
	 */
	public function get_user_by_mailchimp_id( $id ) {
		return $this->get_first_user(
			array(
				'meta_key'     => $this->meta_key,
				'meta_value'   => $id,
				'limit' => 1
			)
		);
	}

	/**
	 * @return WP_User
	 */
	public function get_current_user() {
		return wp_get_current_user();
	}

	/**
	 * @param array $args
	 *
	 * @return null|WP_User
	 */
	public function get_first_user( $args = array() ) {
		$users = $this->get( array( 'limit' => 1 ) );

		if( ! is_array( $users ) || empty( $users ) ) {
			return null;
		}

		return $users[0];
	}

	/**
	 * TODO: Run filter on result
	 *
	 * @return int
	 */
	public function count_subscribers() {
		global $wpdb;

		$sql = "SELECT COUNT(u.ID) FROM $wpdb->users u INNER JOIN $wpdb->usermeta um1 ON um1.user_id = u.ID";

		if( '' !== $this->role ) {
			$sql .= " AND um1.meta_key = %s";
			$sql .= " INNER JOIN $wpdb->usermeta um2 ON um2.user_id = um1.user_id WHERE um2.meta_key = %s AND um2.meta_value LIKE %s";

			$query = $wpdb->prepare( $sql, $this->meta_key, $wpdb->prefix . 'capabilities', '%%' . $this->role . '%%' );
		} else {
			$sql .= " WHERE um1.meta_key = %s";
			$query = $wpdb->prepare( $sql, $this->meta_key );
		}

		// now get number of users with meta key
		$subscriber_count = $wpdb->get_var( $query );
		return (int) $subscriber_count;
	}

	/**
	 * @param $user
	 * @return WP_User
	 *
	 * @throws Exception
	 */
	public function user( $user ) {

		if( ! is_object( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		if( ! $user instanceof WP_User ) {
			throw new Exception( sprintf( 'Invalid user ID: %d', $user ) );
		}

		return $user;
	}

	/**
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function should( WP_User $user ) {
		$sync = true;

		// if role is set, make sure user has that role
		if( ! empty( $this->role ) && ! in_array( $this->role, $user->roles ) ) {
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
	 * @param int $user_id
	 *
	 * @return string
	 */
	public function get_subscriber_uid( $user_id ) {
		$subscriber_uid = get_user_meta( $user_id, $this->meta_key, true );

		if( is_string( $subscriber_uid ) ) {
			return $subscriber_uid;
		}

		return '';
	}

	/**
	 * @param int $user_id
	 * @param string $subscriber_uid
	 */
	public function set_subscriber_uid( $user_id, $subscriber_uid ) {
		update_user_meta( $user_id, $this->meta_key, $subscriber_uid );
	}

	/**
	 * @param int $user_id
	 */
	public function delete_subscriber_uid( $user_id ) {
		delete_user_meta( $user_id, $this->meta_key );
	}
}