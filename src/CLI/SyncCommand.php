<?php

namespace MC4WP\Sync\CLI;

use MC4WP\Sync\Wizard;
use WP_CLI, WP_CLI_Command;
use MC4WP\Sync\ListSynchronizer;

class SyncCommand extends WP_CLI_Command {

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
	 *     wp sync --role=administrator
	 *
	 * @synopsis [--role=<role>]
	 *
	 * @subcommand sync-all
	 */
	public function synchronize_all( $args, $assoc_args ) {

		global $wpdb;

		$wizard = new Wizard( $this->options['list'],  $this->options );
		$user_role = ( isset( $assoc_args['role'] ) ) ? $assoc_args['role'] : '';

		// start by counting all users
		$user_count = $wizard->get_user_count( $user_role );
		WP_CLI::line( "Found $user_count users." );

		// query users in batches of 50
		$processed = 0;
		while( $processed < $user_count ) {

			$batch = $wizard->get_users( $user_role, $processed );

			if( $batch ) {
				$wizard->subscribe_users( $batch );
				$processed += count( $batch );
			}


		}

		WP_CLI::line("Synced $processed users.");

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
	 *     wp sync-user 5
	 *
	 * @synopsis <user_id>
	 *
	 * @subcommand sync-user
	 */
	public function synchronize_user( $args, $assoc_args ) {

		$user_id = absint( $args[0] );

		$wizard = new Wizard(  $this->options['list'],  $this->options );
		$result = $wizard->subscribe_users( array( $user_id ) );

		if( $result ) {
			WP_CLI::line( "User successfully synced!" );
		} else {
			WP_CLI::error( "Error while syncing user #" . $user_id );
		}

	}
}
