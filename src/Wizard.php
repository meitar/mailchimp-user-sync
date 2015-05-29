<?php


namespace MailChimp\Sync;

class Wizard {

	/**
	 * @var string
	 */
	protected $error = '';

	/**
	 * Constructor
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
	 * @return int
	 */
	public function get_user_count() {
		$result = $this->db->get_var( "SELECT count(ID) FROM {$this->db->users} WHERE user_email != ''" );
		return $result;
	}

	/**
	 * Responds with an array of all user ID's
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return mixed
	 */
	public function get_users( $offset = 0, $limit = 50 ) {

		// query users in database, but only users with a valid email
		$sql = "SELECT ID, user_login AS username, user_email AS email
			FROM {$this->db->users}
			WHERE user_email != ''
			LIMIT %d, %d";

		$query = $this->db->prepare( $sql, $offset, $limit );
		return $this->db->get_results( $query, OBJECT );
	}

	/**
	 * Subscribes the provided user ID's
	 *
	 * @param array $user_ids
	 * @return bool
	 */
	public function subscribe_users( array $user_ids ) {

		// instantiate list syncer for selected list
		$syncer = new ListSynchronizer( $this->options['list'], $this->options );

		// loop through user ID's
		$result = false;
		foreach( $user_ids as $user_id ) {
			$result = $syncer->update_subscriber( $user_id );
		}

		if( $result ) {
			return true;
		}

		// get api error
		$api = mc4wp_get_api();
		$this->error = $api->get_error_message();
		return false;
	}

	/**
	 * @return string
	 */
	public function get_error() {
		return $this->error;
	}
}