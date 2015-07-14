<?php


namespace MailChimp\Sync;

class Wizard {

	/**
	 * @var string
	 */
	protected $error = '';

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

		// query users in database, but only users with a valid email
		$users = get_users(
			array(
				'role' => $role,
				'offset' => $offset,
				'limit' => $limit,
				'fields' => array( 'ID', 'user_login', 'user_email' )
			)
		);

		return $users;
	}

	/**
	 * Subscribes the provided user ID's
	 *
	 * @param array $user_ids
	 * @return bool
	 */
	public function subscribe_users( array $user_ids ) {

		// instantiate list syncer for selected list
		// use an empty role here, since user_id should already be filtered on a role
		$syncer = new ListSynchronizer( $this->options['list'], '', $this->options );

		// loop through user ID's
		$result = false;
		foreach( $user_ids as $user_id ) {
			$result = $syncer->update_subscriber( $user_id );
		}

		if( $result ) {
			return true;
		}

		$this->error = $syncer->error;
		return false;
	}

	/**
	 * @return string
	 */
	public function get_error() {
		return $this->error;
	}
}