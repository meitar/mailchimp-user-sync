<?php

namespace MailChimp\Sync\CLI;

use WP_CLI;

class CommandProvider {

	/**
	 * Register commands
	 */
	public function register() {
		WP_CLI::add_command( 'mailchimp-sync', 'MailChimp\\Sync\\CLI\\SyncCommand' );
	}

}