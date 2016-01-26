<?php

namespace MC4WP\Sync\CLI;

use MC4WP\Sync\Wizard;
use WP_CLI, WP_CLI_Command;
use MC4WP\Sync\ListSynchronizer;

class Command extends WP_CLI_Command {

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->options = $GLOBALS['mailchimp_sync']->options;

		parent::__construct();
	}

	/**
	 * Synchronize all users (with a given role)
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : User role to synchronize
	 *
	 * ## EXAMPLES
	 *
	 *     wp mailchimp-sync all --role=administrator
	 *
	 * @synopsis [--role=<role>]
	 *
	 * @subcommand all
	 */
	public function all( $args, $assoc_args ) {

		$wizard = new Wizard( $this->options['list'],  $this->options );
		$user_role = ( isset( $assoc_args['role'] ) ) ? $assoc_args['role'] : '';

		// start by counting all users
		$users = $wizard->get_users( $user_role );
		$count = count( $users );

		WP_CLI::line( "$count users found." );

		// show progress bar
		$notify = \WP_CLI\Utils\make_progress_bar( __( 'Working', 'mailchim-sync'), $count );
		$user_ids = wp_list_pluck( $users, 'ID' );

		foreach( $user_ids as $user_id ) {
			$wizard->subscribe_user( $user_id );
			$notify->tick();
		}

		$notify->finish();

		WP_CLI::success( "Done!" );
	}

	/**
	 * Synchronize a single user
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : ID of the user to synchronize
	 *
	 * ## EXAMPLES
	 *
	 *     wp mailchimp-sync user 5
	 *
	 * @synopsis <user_id>
	 *
	 * @subcommand user
	 */
	public function user( $args, $assoc_args ) {

		$user_id = absint( $args[0] );

		$wizard = new Wizard(  $this->options['list'],  $this->options );
		$result = $wizard->subscribe_user( $user_id );

		if( $result ) {
			WP_CLI::line( "User successfully synced!" );
		} else {
			WP_CLI::error( "Error while syncing user #" . $user_id );
		}

	}

	/**
	 * @deprecated 1.4
	 * @subcommand sync-user
	 */
	public function sync_user( $args, $assoc_args ) {
		$this->user( $args, $assoc_args );
	}

	/**
	 * @deprecated 1.4
	 * @subcommand sync-all
	 */
	public function sync_all( $args, $assoc_args ) {
		$this->all( $args, $assoc_args );
	}
}
