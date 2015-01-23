<?php

namespace MailChimp\Sync;

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
	const VERSION = '1.0';

	/**
	 * @const FILE
	 */
	const FILE = __FILE__;

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
	private $options = array();

	/**
	 * @var
	 */
	private static $instance;

	/**
	 * @return Plugin
	 */
	public static function instance() {

		if( ! self::$instance ) {
			self::$instance = new Plugin;
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {

		require __DIR__ . '/vendor/autoload.php';

		// Load plugin files on a later hook
		add_action( 'plugins_loaded', array( $this, 'load' ), 90 );

		$this->options = $this->load_options();
	}

	/**
	 * Let's go...
	 */
	public function load() {

		if( $this->options['list'] != '' ) {
			new ListSynchronizer( $this->options['list'] );
		}

		if( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

		} else {
			new Admin\Manager( $this->options );
		}
	}

	/**
	 * @return array
	 */
	private function load_options() {

		$options = (array) get_option( self::OPTION_NAME, array() );

		$defaults = array(
			'list' => '',
			'double_optin' => 1,
			'send_welcome' => 1
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

$GLOBALS['MailChimp_Sync'] = Plugin::instance();