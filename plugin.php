<?php

namespace MailChimp\Sync;

// Prevent direct file access
use MailChimp\Sync\CLI\CommandProvider;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

final class Plugin {

	/**
	 * @const VERSION
	 */
	const VERSION = '0.1.2';

	/**
	 * @const FILE
	 */
	const FILE = MAILCHIMP_SYNC_FILE;

	/**
	 * @const DIR
	 */
	const DIR = __DIR__;

	/**
	 * @const OPTION_NAME Option name
	 */
	const OPTION_NAME = 'mailchimp_sync';

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * Constructor
	 */
	public function __construct() {	}

	public function dependencies_met() {
		// check dependencies and only continue if installed
		$dependencyCheck = new DependencyCheck();
		return $dependencyCheck->check();
	}

	/**
	 * Let's go...
	 *
	 * Runs at `plugins_loaded` priority 30.
	 */
	public function load() {

		// load plugin options
		$this->options = $this->load_options();

		// if a list was selected, initialise the ListSynchronizer class
		if( $this->options['list'] != '' ) {
			$listSyncer = new ListSynchronizer( $this->options['list'], $this->options['role'], $this->options );
			$listSyncer->add_hooks();
		}

		if( defined( 'WP_CLI' ) && WP_CLI ) {
			$commands = new CommandProvider();
			$commands->register();
		}

		// Load area-specific code
		if( ! is_admin() ) {

		} elseif( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$ajax = new AjaxListener( $this->options );
			$ajax->add_hooks();
		} else {
			$admin = new Admin\Manager( $this->options );
			$admin->add_hooks();
		}
	}

	/**
	 * @return array
	 */
	private function load_options() {

		$options = (array) get_option( self::OPTION_NAME, array() );

		$defaults = array(
			'list' => '',
			'double_optin' => 0,
			'send_welcome' => 0,
			'role' => ''
		);

		$options = array_merge( $defaults, $options );

		return $options;
	}

	/**
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

}

add_action( 'plugins_loaded', function() {
	$plugin = new Plugin();

	if( $plugin->dependencies_met() ) {
		$plugin->load();
	}

	$GLOBALS['MailChimp_Sync'] = $plugin;
} );
