<?php

namespace MC4WP\Sync;

use MC4WP\Sync\CLI\CommandProvider;

defined( 'ABSPATH' ) or exit;

// load autoloader
require dirname( __FILE__ ) . '/vendor/autoload.php';

// instantiate plugin
$plugin = new Plugin();

// if a list was selected, initialise the ListSynchronizer class
if( $plugin->options['list'] != '' && $plugin->options['enabled'] ) {
	$scheduler = new Producer();
	$scheduler->add_hooks();

	$list_synchronizer = new ListSynchronizer( $plugin->options['list'], $plugin->options['role'], $plugin->options );
	$list_synchronizer->add_hooks();
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
	$commands = new CommandProvider();
	$commands->register();
}