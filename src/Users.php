<?php

namespace MC4WP\Sync;

use WP_User;
use WP_User_Query;

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
	 * @param string $meta_key
	 */
	public function __construct( $meta_key ) {
		$this->meta_key = $meta_key;
	}

	/**
	 * @param string $role
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array
	 */
	public function get( $role = '', $offset = 0, $limit = 50  ) {
		$user_query = new WP_User_Query(
			array(
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
				'role' => $role,
				'fields' => array( 'ID', 'user_login', 'user_email' ),
				'orderby' => 'meta_value'
			)
		);

		return $user_query->get_results();
	}

	/**
	 * @param string $role
	 *
	 * @return int
	 */
	public function count( $role = '' ) {
		$count = count_users();

		if( '' !== $role ) {
			return isset( $count['avail_roles'][ $role  ] ) ? $count['avail_roles'][ $role ] : 0;
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
	 * @param $role
	 *
	 * @return WP_User|null
	 */
	public function get_first_user_with_role( $role ) {
		return $this->get_first_user(
			array(
				'role' => $role
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
		$args['limit'] = 1;
		$users = get_users( $args );

		if( ! is_array( $users ) || empty( $users ) ) {
			return null;
		}

		return $users[0];
	}

	/**
	 * @param string $user_role
	 *
	 * @return int
	 */
	public function count_subscribers( $user_role = '' ) {
		global $wpdb;

		$sql = "SELECT COUNT(u.ID) FROM $wpdb->users u INNER JOIN $wpdb->usermeta um1 ON um1.user_id = u.ID";

		if( '' !== $user_role ) {
			$sql .= " AND um1.meta_key = %s";
			$sql .= " INNER JOIN $wpdb->usermeta um2 ON um2.user_id = um1.user_id WHERE um2.meta_key = %s AND um2.meta_value LIKE %s";

			$query = $wpdb->prepare( $sql, $this->meta_key, $wpdb->prefix . 'capabilities', '%%' . $user_role . '%%' );
		} else {
			$sql .= " WHERE um1.meta_key = %s";
			$query = $wpdb->prepare( $sql, $this->meta_key );
		}

		// now get number of users with meta key
		$subscriber_count = $wpdb->get_var( $query );
		return (int) $subscriber_count;
	}
}