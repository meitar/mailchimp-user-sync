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
	 * Constructor
	 */
	public function __construct() {

		require __DIR__ . '/vendor/autoload.php';

		// Load plugin files on a later hook
		add_action( 'plugins_loaded', array( $this, 'load' ), 90 );
	}

	/**
	 * Let's go...
	 */
	public function load() {

		if( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

		} else {
			new Admin\Manager();
		}
	}

	/**
	 * Get an option by its key
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public static function get_option( $key ) {

		$options = self::get_options();

		if( array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		return null;
	}

	/**
	 * @return array
	 */
	public static function get_options() {
		static $options;

		if( is_null( $options ) ) {

			$options = (array) get_option( self::OPTION_NAME, array() );

			$defaults = array(
				'list' => '',
				'double_optin' => 1,
				'send_welcome' => 1
			);

			$options = array_merge( $defaults, $options );
		}

		return $options;
	}

}

$GLOBALS['MailChimp_Sync'] = new Plugin;