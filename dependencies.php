<?php

// Check for old MailChimp for WordPress Pro or 3.0+
if( defined( 'MC4WP_VERSION' ) && version_compare( MC4WP_VERSION, '2.7', '>=' ) ) {
	return true;
}

// check for free plugin v2.x
if( defined( 'MC4WP_LITE_VERSION' ) && version_compare( MC4WP_LITE_VERSION, '2.3', '>=' ) ) {
	return true;
}

add_action( 'admin_notices', function() {
	?>
	<div class="updated">
		<p><?php printf( __( 'Please install <a href="%s">%s</a> in order to use %s.', 'mailchimp-sync' ), 'https://wordpress.org/plugins/mailchimp-for-wp/', 'MailChimp for WordPress', 'MailChimp Sync' ); ?></p>
	</div>
<?php
} );

// Tell plugin not to proceed
return false;
