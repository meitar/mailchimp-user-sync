<?php

namespace MailChimp\Sync;

use MailChimp\Sync\CLI\CommandProvider;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

final class Plugin {

	/**
	 * @const VERSION
	 */
	const VERSION = '1.1.2';

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

	/**
	 * @var ListSynchronizer
	 */
	public $list_synchronizer;

	/**
	 * Let's go...
	 *
	 * Runs at `plugins_loaded` priority 30.
	 */
	public function init() {

		// load plugin options
		$this->options = $this->load_options();

		// if a list was selected, initialise the ListSynchronizer class
		if( $this->options['list'] != '' && $this->options['enabled'] ) {
			$this->list_synchronizer = new ListSynchronizer( $this->options['list'], $this->options['role'], $this->options );
			$this->list_synchronizer->add_hooks();
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
			$admin = new Admin\Manager( $this->options, $this->list_synchronizer );
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
			'role' => '',
			'enabled' => 1,
			'field_mappers' => array()
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

// Instantiate plugin on a later hook.
add_action( 'plugins_loaded', function() {

	$ready = include __DIR__  .'/dependencies.php';
	if( $ready ) {
		$plugin = new Plugin();
		$plugin->init();
		$GLOBALS['MailChimp_Sync'] = $plugin;
	}

}, 20 );