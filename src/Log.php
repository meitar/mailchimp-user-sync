<?php

namespace MailChimp\Sync;

/**
 * Class Log
 *
 * @package MailChimp\Sync
 */
class Log {

	/**
	 * @var string
	 */
	protected $file_path = '';

	/**
	 * @var string
	 */
	protected $file_name = 'mailchimp-sync.log';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->file_path = $this->determine_file_path();
	}

	/**
	 * Determine the full path of the log file.
	 *
	 * @return string
	 */
	protected function determine_file_path() {
		$upload_dir = wp_upload_dir();

		if( isset( $upload_dir['basedir'] ) ) {
			return $upload_dir['basedir'] . '/' . $this->file_name;
		}

		return dirname( MAILCHIMP_SYNC_FILE ) . '/' . $this->file_name;
	}

	/**
	 * @param string $text
	 */
	public function write( $text ) {
		error_log( $text, 3, $this->file_path );
	}

	/**
	 * Write a new line to the log file.
	 *
	 * @param string $text
	 */
	public function write_line( $text ) {
		$this->write( $text . PHP_EOL );
	}

	/**
	 * Deletes the log file
	 */
	public function clear() {
		if( file_exists( $this->file_path ) ) {
			unlink( $this->file_path );
		}
	}
}