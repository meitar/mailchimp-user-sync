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
		add_filter( 'mc4wp_menu_items', array( $this, 'add_menu_items' ) );
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

		add_filter( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
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

	/**
	 * Listen for stuff..
	 */
	private function listen() {

	}

	/**
	 * Register menu pages
	 *
	 * @param $items
	 *
	 * @return
	 */
	public function add_menu_items( $items ) {

		$item = array(
			'title' => __( 'MailChimp Sync', 'mailchimp-sync' ),
			'text' => __( 'Sync', 'mailchimp-sync' ),
			'slug' => 'sync',
			'callback' => array( $this, 'show_settings_page' )
		);

		// insert item before the last menu item
		array_splice( $items, count( $items ) - 1, 0, array( $item ) );

		return $items;
	}

	/**
	 * Load assets if we're on the settings page of this plugin
	 *
	 * @return bool
	 */
	public function load_assets() {

		if( ! isset( $_GET['page'] ) || $_GET['page'] !== 'mailchimp-for-wp-sync' ) {
			return false;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'mailchimp-sync-admin', $this->asset_url( "/css/admin{$min}.css" ) );

		wp_enqueue_script( 'es5-polyfill', 'https://cdnjs.cloudflare.com/ajax/libs/es5-shim/4.0.3/es5-shim.min.js' );
		wp_enqueue_script( 'mithril', $this->asset_url( "/js/deps/mithril{$min}.js" ), array( 'es5-polyfill' ), Plugin::VERSION, true );
		wp_enqueue_script( 'mailchimp-sync-wizard', $this->asset_url( "/js/wizard{$min}.js" ), array( 'mithril' ), Plugin::VERSION, true );

		return true;
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