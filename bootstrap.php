<?php

namespace MC4WP\Sync;

use MC4WP_Queue as Queue;
use WP_CLI;

defined( 'ABSPATH' ) or exit;

// load autoloader
require dirname( __FILE__ ) . '/vendor/autoload.php';

// instantiate plugin
$plugin = new Plugin();

// expose plugin in a global. YUCK!
$GLOBALS['mailchimp_sync'] = $plugin;

// default to null object
$list_synchronizer = null;

// if a list was selected, initialise the ListSynchronizer class
if( ! empty( $plugin->options['list'] ) ) {

	// instantiate synchronizer
	$list_synchronizer = new ListSynchronizer( $plugin->options['list'], $plugin->options['role'], $plugin->options );
	$list_synchronizer->add_hooks();

	// if auto-syncing is enabled, setup queue and worker
	if( $plugin->options['enabled'] ) {

		// create a job queue
		$queue = new Queue( 'mc4wp_sync_queue' );

		// create a worker and have it work on "init" when doing CRON
		$worker = new Worker( $queue, $list_synchronizer );
		$worker->add_hooks();

		if( defined( 'DOING_CRON' ) && DOING_CRON ) {
			add_action( 'init', array( $worker, 'work' ) );
		}
	}
}

if( ! is_admin() ) {

	// public section
	if( $list_synchronizer instanceof ListSynchronizer ) {
		$webhook_listener = new Webhook\Listener( $list_synchronizer->meta_key, $plugin->options['field_mappers'] );
		$webhook_listener->add_hooks();
	}

} elseif( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

	// ajax listeners
	$ajax = new AjaxListener( $plugin->options );
	$ajax->add_hooks();
} else {

	// admin screens
	$admin = new Admin\Manager( $plugin->options, $list_synchronizer );
	$admin->add_hooks();
}

// WP CLI Commands
if( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'mailchimp-sync', 'MC4WP\\Sync\\CLI\\Command' );
}