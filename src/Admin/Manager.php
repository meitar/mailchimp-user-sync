<?php

namespace MailChimp\Sync\Admin;

use MailChimp\Sync\Plugin;
use MailChimp\Sync\ListSynchronizer;
use WP_User;

class Manager {

	const SETTINGS_CAP = 'manage_options';

	/**
	 * @var array $options
	 */
	private $options;

	/**
	 * @var ListSynchronizer
	 */
	protected $list_synchronizer;

	/**
	 * Constructor
	 * @param array $options
	 */
	public function __construct( array $options, $list_synchronizer ) {
		$this->options = $options;
		$this->plugin_slug = basename( Plugin::DIR ) . '/mailchimp-sync.php';
		$this->list_synchronizer = $list_synchronizer;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_filter( 'mc4wp_menu_items', array( $this, 'add_menu_items' ) );
	}

	/**
	 * Runs on `admin_init`
	 */
	public function init() {

		// only run for administrators
		if( ! current_user_can( self::SETTINGS_CAP ) ) {
			return false;
		}

		// register settings
		register_setting( Plugin::OPTION_NAME, Plugin::OPTION_NAME, array( $this, 'sanitize_settings' ) );

		// add link to settings page from plugins page
		add_filter( 'plugin_action_links_' . $this->plugin_slug, array( $this, 'add_plugin_settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links'), 10, 2 );

		// only show this if user has settings cap
		add_action( 'edit_user_profile', array( $this, 'add_user_actions' ) );

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

		if( ! isset( $_GET['mc4wp-sync-action'] ) ) {
			return false;
		}

		$action = (string) $_GET['mc4wp-sync-action'];

		switch( $action ) {
			case 'sync-user':
				$user_id = intval( $_GET['user_id'] );
				$success = $this->list_synchronizer->update_subscriber( $user_id );

				// todo: show some visual feedback
				break;
		}
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
	 *
	 */
	public function add_user_actions( WP_User $user ) {

		if( ! $this->list_synchronizer instanceof ListSynchronizer ) {
			return;
		}

		$is_subscribed = $this->list_synchronizer->get_user_subscriber_uid( $user );
		$sync_url = add_query_arg(
			array(
				'mc4wp-sync-action' => 'sync-user',
				'user_id' => $user->ID
			)
		);
		?>

		<h3><?php _e( 'MailChimp Status', 'mailchimp-sync' ); ?></h3>

		<p><?php printf( __( 'To change your list synchronization settings, please go to the <a href="%s">MailChimp Sync settings page</a>.', 'mailchimp-sync' ), admin_url( 'admin.php?page=mailchimp-for-wp-sync' ) ); ?></p>

		<table class="form-table">
			<tr>
				<th><?php $is_subscribed ? _e( 'Subscribed', 'mailchimp-for-wp' ) : _e( 'Not Subscribed', 'mailchimp-sync' ); ?></th>
				<td>
					<a href="<?php echo esc_url( $sync_url ); ?>" class="button">
						<?php $is_subscribed ? _e( 'Update' ) : _e( 'Subscribe', 'mailchimp-for-wp' ); ?>
					</a>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Add the settings link to the Plugins overview
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_plugin_settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=mailchimp-for-wp-sync' ), __( 'Settings', 'mailchimp-for-wp' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Adds meta links to the plugin in the WP Admin > Plugins screen
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if( $file !== $this->plugin_slug ) {
			return $links;
		}

		$links[] = sprintf( __( 'An add-on for %s', 'mailchimp-sync' ), '<a href="https://mc4wp.com/#utm_source=wp-plugin&utm_medium=mailchimp-top-bar&utm_campaign=plugins-page">MailChimp for WordPress</a>' );
		return $links;
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
		wp_enqueue_script( 'mithril', $this->asset_url( "/js/mithril{$min}.js" ), array( 'es5-polyfill' ), Plugin::VERSION, true );
		wp_enqueue_script( 'mailchimp-sync-wizard', $this->asset_url( "/js/admin{$min}.js" ), array( 'mithril' ), Plugin::VERSION, true );

		return true;
	}

	/**
	 * Outputs the settings page
	 *
	 * @todo Add field mapping
	 */
	public function show_settings_page() {

		$lists = $this->get_mailchimp_lists();

		if( $this->options['list'] !== '' ) {
			$status_indicator = new StatusIndicator( $this->options['list'], $this->options['role'] );
			$status_indicator->check();
			$selected_list = isset( $lists[ $this->options['list'] ] ) ? $lists[ $this->options['list'] ] : null;
			$field_mapper = new FieldMapper( $this->options['field_mappers'], $selected_list->merge_vars );
		} else {
			$field_mapper = new FieldMapper( $this->options['field_mappers'] );
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

	/**
	 * @param $option_name
	 *
	 * @return string
	 */
	protected function name_attr( $option_name ) {

		if( substr( $option_name, -1 ) !== ']' ) {
			return Plugin::OPTION_NAME . '[' . $option_name . ']';
		}

		return Plugin::OPTION_NAME . $option_name;
	}

	/**
	 * @param array $dirty
	 *
	 * @return array $clean
	 */
	public function sanitize_settings( array $dirty ) {

		// todo: perform some actual sanitization
		$clean = $dirty;

		if( isset( $clean['field_mappers'] ) ) {

			if( ! is_array( $clean['field_mappers'] ) ) {
				unset( $clean['field_mappers'] );
			}

			foreach( $clean['field_mappers'] as $key=> $mapper ) {
				if( empty( $mapper['user_field'] ) || empty( $mapper['mailchimp_field'] ) ) {
					unset( $clean['field_mappers'][ $key ] );
				}
			}

		}

		return $clean;
	}

	/**
	 * Helper function to retrieve MailChimp lists through MailChimp for WordPress
	 *
	 * Will try v3.0+ first, then fallback to older versions.
	 *
	 * @return array
	 */
	protected function get_mailchimp_lists() {

		if( class_exists( 'MC4WP_MailChimp_Tools' ) && method_exists( 'MC4WP_MailChimp_Tools', 'get_lists' ) ) {
			return \MC4WP_MailChimp_Tools::get_lists();
		}

		/** @deprecated MailChimp for WordPress v3.0  */
		if( class_exists( 'MC4WP_MailChimp' ) ) {
			$mailchimp = new \MC4WP_MailChimp();
			return $mailchimp->get_lists();
		}

		return array();
	}




}