<?php


namespace MC4WP\Sync;

/**
 * Class ShutdownWorker
 * @package MC4WP\Sync
 */
class ShutdownWorker implements Worker {

	/**
	 * @var array
	 */
	protected $tasks = array();

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		register_shutdown_function( array( $this, 'work' ) );
	}

	/**
	 * @param string $event
	 * @param array $args
	 * @return bool
	 */
	public function assign( $event, array $args = array() ) {

		$task = array_merge( array( $event ), $args );

		// only add each task once
		if( in_array( $task, $this->tasks ) ) {
			return false;
		}

		$this->tasks[] = $task;
		return true;
	}

	/**
	 * Fire a `do_action` for each task
	 */
	public function work() {
		while( ! empty( $this->tasks ) ) {
			$task = array_shift( $this->tasks );
			call_user_func_array( 'do_action', $task );
		}
	}
}