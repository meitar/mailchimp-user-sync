<?php


namespace MC4WP\Sync;

use WP_User_Query;

class Wizard {

	/**
	 * @var string
	 */
	protected $error = '';

	/**
	 * @var string
	 */
	protected $list_id = '';

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * Constructor
	 *
	 * @param       $list_id
	 * @param array $options
	 */
	public function __construct( $list_id, $options = array() ) {

		$this->list_id = $list_id;
		$this->options = $options;

		global $wpdb;
		$this->db = $wpdb;

		// instantiate list syncer for selected list
		// use an empty role here, since user_id should already be filtered on a role
		$this->sync = new ListSynchronizer( $list_id, '', $this->options );
	}

	/**
	 * Get user count
	 *
	 * @param string $role
	 *
	 * @return int
	 */
	public function get_user_count( $role = '' ) {
		$count = count_users();

		if( '' !== $role ) {
			return isset( $count['avail_roles'][ $role ] ) ? $count['avail_roles'][ $role ] : 0;
		}

		return $count['total_users'];
	}

	/**
	 * Responds with an array of all user ID's
	 *
	 * @param string $role
	 * @param int    $offset
	 * @param int    $limit
	 *
	 * @return mixed
	 */
	public function get_users( $role = '', $offset = 0, $limit = 50 ) {

		$user_query = new WP_User_Query(
			array(
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => $this->sync->meta_key,
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => $this->sync->meta_key,
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
	 * Subscribes the provided user ID's
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function subscribe_user( $user_id ) {

		// loop through user ID's
		$result = $this->sync->subscribe_user( $user_id );
		$this->error = $this->sync->error;

		return !!$result;
	}

	/**
	 * @return string
	 */
	public function get_error() {
		return $this->error;
	}
}