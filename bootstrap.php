<?php

namespace MC4WP\Sync;

use MC4WP\Sync\CLI\CommandProvider;
use MC4WP_Queue as Queue;

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
if( ! empty( $plugin->options['list'] ) && $plugin->options['enabled'] ) {

	// instantiate synchronizer
	$list_synchronizer = new ListSynchronizer( $plugin->options['list'], $plugin->options['role'], $plugin->options );
	$list_synchronizer->add_hooks();

	// create a job queue
	$queue = new Queue( 'mc4wp_sync_queue' );

	// create a worker and have it work on "init" when doing CRON
	$worker = new Worker( $queue, $list_synchronizer );
	$worker->add_hooks();

	if( defined( 'DOING_CRON' ) && DOING_CRON ) {
		add_action( 'init', array( $worker, 'work' ) );
	}
}

// Load area-specific code
if( ! is_admin() ) {
	// TODO: make this optional
	$webhook_listener = new Webhook\Listener( $plugin->options );
	$webhook_listener->add_hooks();
} elseif( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	$ajax = new AjaxListener( $plugin->options );
	$ajax->add_hooks();
} else {
	$admin = new Admin\Manager( $plugin->options, $list_synchronizer );
	$admin->add_hooks();
}

if( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'mailchimp-sync', 'MC4WP\\Sync\\CLI\\Command' );
}