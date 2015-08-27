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
		$this->user_fields = $this->get_current_user_meta_keys();
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
	public function get_current_user_meta_keys() {
		return array_keys( $this->get_current_user_meta() );
	}

	/**
	 * @return array
	 */
	protected function get_current_user_meta() {
		$user = wp_get_current_user();
		return $this->get_user_meta( $user );
	}

	/**
	 * @param WP_User $user
	 *
	 * @return array
	 */
	protected function get_user_meta( WP_User $user ) {

		$meta = array_map(
			function( $a ){ return $a[0]; },
			get_user_meta( $user->ID )
		);

		foreach( $meta as $key => $value ) {
			// only use direct strings
			if( ! is_string( $value )
			    || strpos( $key, 'wp_' ) === 0
			    || strpos( $key, '_' ) === 0
			    || is_serialized( $value ) ) {
				unset( $meta[ $key ] );
				continue;
			}
		}

		return $meta;
	}

}