<?php

namespace MailChimp\Sync\Admin;

use WP_User;

class FieldMapper {

	/**
	 * @var array
	 */
	public $map = array();

	/**
	 * @var array
	 */
	public $mailchimp_fields = array();

	/**
	 * @var array
	 */
	public $user_fields = array();

	/**
	 * @var array
	 */
	public $available_mailchimp_fields = array();

	/**
	 * @param array $map
	 * @param array $mailchimp_fields
	 */
	public function __construct( array $map, array $mailchimp_fields = array() ) {
		$this->map = $map;

		// add empty map to end of array
		$this->map[] = array(
			'user_field' => '',
			'mailchimp_field' => ''
		);

		$this->mailchimp_fields = $mailchimp_fields;
		$this->available_mailchimp_fields = $this->check_available_mailchimp_fields();
		$this->user_fields = $this->get_current_user_fields();
	}

	/**
	 * @return array
	 */
	function check_available_mailchimp_fields() {
		$available = array();

		foreach( $this->mailchimp_fields as $field ) {

			if( $field->tag === 'EMAIL' ) {
				continue;
			}

			foreach( $this->map as $row ) {
				if( $row['mailchimp_field'] === $field->tag ) {
					continue 2;
				}
			}

			$available[] = $field->tag;
		}

		return $available;
	}

	/**
	 * @return array
	 */
	public function get_current_user_fields() {

		$default_fields = $this->get_current_user_default_fields();
		$custom_fields = $this->get_current_user_custom_fields();

		$meta = array_merge( $custom_fields, $default_fields );

		sort( $meta );
		return $meta;
	}

	/**
	 * @return array
	 */
	protected function get_current_user_default_fields() {
		$user = wp_get_current_user();
		$hidden_fields = array( 'user_pass', 'user_status', 'spam', 'deleted', 'user_activation_key' );
		$fields = array();

		foreach( $user->data as $field => $value ) {

			if( in_array( $field, $hidden_fields ) ) {
				continue;
			}

			$fields[] = $field;
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	protected function get_current_user_custom_fields() {
		$user = wp_get_current_user();
		return $this->get_user_custom_fields( $user );
	}

	/**
	 * @param WP_User $user
	 *
	 * @return array
	 */
	protected function get_user_custom_fields( WP_User $user ) {

		$meta = array_map(
			function( $a ){ return $a[0]; },
			get_user_meta( $user->ID )
		);

		$hidden_fields = array(
			'show_admin_bar_front',
			'use_ssl',
			'comment_shortcuts',
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'rich_editing',
			'admin_color'
		);

		$fields = array();

		foreach( $meta as $key => $value ) {
			// only use direct strings
			if( ! is_string( $value )
			    || strpos( $key, 'wp_' ) === 0
			    || strpos( $key, '_' ) === 0
			    || is_serialized( $value )
				|| in_array( $key, $hidden_fields )) {
				continue;
			}

			$fields[] = $key;
		}

		return $fields;
	}

}