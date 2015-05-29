<?php

namespace MailChimp\Sync\CLI;

use MailChimp\Sync\Wizard;
use WP_CLI, WP_CLI_Command;
use MailChimp\Sync\ListSynchronizer;

class SyncCommand extends WP_CLI_Command {

	/**
	 * Check the VAT on all payments
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand sync-all
	 */
	public function synchronise_all( $args, $assoc_args ) {

		global $wpdb;

		$wizard = new Wizard( $GLOBALS['MailChimp_Sync']->options['list'], $GLOBALS['MailChimp_Sync']->options );

		// start by counting all users
		$user_count = $wizard->get_user_count();
		WP_CLI::line( "Found $user_count users." );

		// query users in batches of 50
		$processed = 0;
		while( $processed < $user_count ) {

			$batch = $wizard->get_users( $processed );

			if( $batch ) {
				$wizard->subscribe_users( $batch );
				$processed += count( $batch );
			}


		}

		WP_CLI::line("Synced $processed users.");

	}

	/**
	 * @param $args
	 * @param $assoc_args
	 * @subcommand sync-user
	 */
	public function synchronise_user( $args, $assoc_args ) {

		$opts = $GLOBALS['MailChimp_Sync']->options;
		$user_id = absint( $args[0] );

		$wizard = new Wizard( $GLOBALS['MailChimp_Sync']->options['list'], $GLOBALS['MailChimp_Sync']->options );
		$result = $wizard->subscribe_users( array( $user_id ) );

		if( $result ) {
			WP_CLI::line( "User successfully synced!" );
		} else {
			WP_CLI::line( "Error while syncing user #" . $user_id );
		}

	}
}
