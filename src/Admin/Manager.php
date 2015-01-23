<?php

namespace MailChimp\Sync\Admin;

use MailChimp\Sync\Plugin;

class Manager {

	const SETTINGS_CAP = 'manage_options';

	/**
	 * @var array $options
	 */
	private $options;

	/**
	 * Constructor
	 * @param array $options
	 */
	public function __construct( array $options ) {

		$this->options = $options;

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Runs on `admin_init`
	 */
	public function init() {

		// only run for administrators
		if( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// register settings
		register_setting( Plugin::OPTION_NAME, Plugin::OPTION_NAME, array( $this, 'sanitize_settings' ) );

		// listen for wphs requests, user is authorized by now
		$this->listen();

		// run upgrade routine
		$this->upgrade_routine();
	}

	/**
	 * Upgrade routine, only runs when needed
	 */
	private function upgrade_routine() {

		$db_version = get_option( 'mailchimp_sync_version', 0 );

		// only run if db version is lower than actual code version
		if ( ! version_compare( $db_version, Plugin::VERSION, '<' ) ) {
			return false;
		}

		// nothing here yet..

		update_option( 'mailchimp_sync_version', Plugin::VERSION );
		return true;
	}

	private function listen() {

	}


	/**
	 * Register menu pages
	 */
	public function menu() {
		add_submenu_page( 'mailchimp-for-wp', __( 'MailChimp Sync', 'mailchimp-sync' ), __( 'Sync', 'mailchimp-sync' ), self::SETTINGS_CAP, 'mailchimp-for-wp-sync', array( $this, 'show_settings_page' ) );
	}

	/**
	 * Outputs the settings page
	 *
	 * @todo Add field mapping
	 */
	public function show_settings_page() {

		$mailchimp = new \MC4WP_MailChimp();
		$lists = $mailchimp->get_lists();

		if( $this->options['list'] !== '' ) {
			$statusIndicator = new StatusIndicator( $this->options['list'] );
		}

		require Plugin::DIR . '/views/settings-page.php';
	}



	/**
	 * @param $url
	 *
	 * @return string
	 */
	protected function asset_url( $url ) {
		return plugins_url( '/assets' . $url, Plugin::FILE );
	}

	protected function name_attr( $option_name ) {
		return Plugin::OPTION_NAME . '[' . $option_name . ']';
	}

	/**
	 * @param array $dirty
	 *
	 * @return array $clean
	 */
	public function sanitize_settings( array $dirty ) {

		// todo: perform some actual sanitization
		$clean = $dirty;

		return $clean;
	}


}