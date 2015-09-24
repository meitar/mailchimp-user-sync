<?php
/*
Plugin Name: MailChimp Sync
Plugin URI: https://mc4wp.com/#utm_source=wp-plugin&utm_medium=mailchimp-sync&utm_campaign=plugins-page
Description: Synchronize your WordPress Users with a MailChimp list.
Version: 1.2
Author: ibericode
Author URI: https://ibericode.com/
Text Domain: mailchimp-sync
Domain Path: /languages
License: GPL v3

MailChimp Sync
Copyright (C) 2015, Danny van Kooten, hi@dannyvankooten.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

// Check if PHP is at the minimum required version
if( version_compare( PHP_VERSION, '5.3', '>=' ) ) {

	define( 'MAILCHIMP_SYNC_FILE', __FILE__ );
	require dirname( __FILE__ ) . '/vendor/autoload.php';
	require dirname( __FILE__ ) . '/plugin.php';
} else {
	require dirname( __FILE__ ) . '/php-backwards-compatibility.php';
}





