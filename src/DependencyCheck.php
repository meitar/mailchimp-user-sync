<?php

namespace MailChimp\Sync;

class DependencyCheck {

	/**
	 * @var bool
	 */
	public $dependencies_installed = false;

	/**
	 * Constructor
	 */
	public function __construct() {}

	public function check() {
		$check = $this->check_dependencies();

		if( ! $check ) {
			$this->add_actions();
		}

		return $check;
	}

	/**
	 * Check if the plugin dependencies are installed
	 * @return bool
	 */
	private function check_dependencies() {

		// check for mailchimp for wordpress pro
		if( defined( 'MC4WP_VERSION' ) && version_compare( MC4WP_VERSION, '2.5.5', '>=' ) ) {
			return true;
		}

		// check for mailchimp for wordpress lite
		if( defined( 'MC4WP_LITE_VERSION' ) && version_compare( MC4WP_LITE_VERSION, '2.2.3', '>=' ) ) {
			return true;
		}

		// check for MailChimp for WordPress core
		if( defined( 'MC4WP_VERSION' ) && version_compare( MC4WP_VERSION, '3.0', '>=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private function add_actions() {
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		return true;
	}

	/**
	 * Outputs admin notice telling the user to install the required dependencies
	 */
	public function admin_notice() {
		?>
		<div class="updated">
			<p><?php printf( __( 'Please install <a href="%s">%s</a> in order to use %s.', 'mailchimp-sync' ), 'https://wordpress.org/plugins/mailchimp-for-wp/', 'MailChimp for WordPress', 'MailChimp Sync' ); ?></p>
		</div>
		<?php
	}
}