<?php
use MailChimp\Sync\Plugin;

defined( 'ABSPATH' ) or exit;
?>
<div class="wrap" id="wp-helpscout">

	<h1 style="line-height: 48px;">MailChimp Sync</h1>

	<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">

		<?php settings_fields( Plugin::OPTION_NAME ); ?>


		<h2><?php _e( 'Settings' ); ?></h2>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><?php _e( 'Sync users with this list', 'mailchimp-sync' ); ?></th>
				<td>
					<?php if( empty( $lists ) ) {
						printf( __( 'No lists found, <a href="%s">are you connected to MailChimp</a>?', 'mailchimp-for-wp' ), admin_url( 'admin.php?page=mailchimp-for-wp' ) ); ?>
					<?php } ?>

					<select name="<?php echo $this->name_attr( 'list' ); ?>" class="widefat">
						<option disabled <?php selected( $options['list'], '' ); ?>><?php _e( 'Select a list..', 'mailchimp-sync' ); ?></option>
						<?php foreach( $lists as $list ) { ?>
							<option value="<?php echo esc_attr( $list->id ); ?>" <?php selected( $options['list'], $list->id ); ?>><?php echo esc_html( $list->name ); ?></option>
=						<?php } ?>
					</select>

				</td>
				<td class="desc"><?php _e( 'Select the list to synchronize your WordPress user base with.' ,'mailchimp-sync' ); ?></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Double opt-in?', 'mailchimp-for-wp' ); ?></th>
				<td class="nowrap">
					<label>
						<input type="radio" name="<?php echo $this->name_attr( 'double_optin' ); ?>" value="1" <?php checked( $options['double_optin'], 1 ); ?> />
						<?php _e( 'Yes', 'mailchimp-for-wp' ); ?>
					</label> &nbsp;
					<label>
						<input type="radio" id="mc4wp_checkbox_double_optin_0" name="<?php echo $this->name_attr( 'double_optin' ); ?>" value="0" <?php checked( $options['double_optin'], 0 ); ?> />
						<?php _e( 'No', 'mailchimp-for-wp' ); ?>
					</label>
				</td>
				<td class="desc"><?php _e( 'Select "yes" if you want people to confirm their email address before being subscribed (recommended)', 'mailchimp-for-wp' ); ?></td>
			</tr>

			<?php $enabled = !$options['double_optin']; ?>
			<tr id="mc4wp-send-welcome"  valign="top" <?php if(!$enabled) { ?>class="hidden"<?php } ?>>
				<th scope="row"><?php _e( 'Send Welcome Email?', 'mailchimp-for-wp' ); ?></th>
				<td class="nowrap">
					<input type="radio" id="mc4wp_checkbox_send_welcome_1" name="<?php echo $this->name_attr( 'send_welcome' ); ?>" value="1" <?php if($enabled) { checked( $options['send_welcome'], 1 ); } else { echo 'disabled'; } ?> />
					<label for="mc4wp_checkbox_send_welcome_1"><?php _e( 'Yes', 'mailchimp-for-wp' ); ?></label> &nbsp;
					<input type="radio" id="mc4wp_checkbox_send_welcome_0" name="<?php echo $this->name_attr( 'send_welcome' ); ?>" value="0" <?php if($enabled) { checked( $options['send_welcome'], 0 ); } else { echo 'disabled'; } ?> />
					<label for="mc4wp_checkbox_send_welcome_0"><?php _e( 'No', 'mailchimp-for-wp' ); ?></label> &nbsp;
				</td>
				<td class="desc"><?php _e( 'Select "yes" if you want to send your lists Welcome Email if a subscribe succeeds (only when double opt-in is disabled).', 'mailchimp-for-wp' ); ?></td>
			</tr>

		</table>

		<?php submit_button(); ?>
	</form>


	<?php if( '' !== $options['list'] ) { ?>
		<h2><?php _e( 'Synchronization', 'mailchimp-for-wp' ); ?></h2>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<?php _e( 'Status', 'mailchimp-sync' ); ?>
				</th>
				<td>
					<?php
					if( $statusIndicator->status ) {
						echo '<span class="status positive">' . __( 'IN SYNC', 'mailchimp-sync' ) . '</span>';
					} else {
						echo '<span class="status negative">' . __( 'OUT OF SYNC', 'mailchimp-sync' ) . '</span>';
					} ?>
				</td>
			</tr>
		</table>

	<?php } ?>

</div>