<?php
/*
 * Plugin Name: Seriously Simple Podcasting - Radio Campus edition
 * Version: 1.11.2
 * Description: Variante de "Seriously Simple Podcasting" pour Radio Campus Grenoble
 * Author: Hugh Lashbrooke, Martin Kirchgessner
 * Requires at least: 4.2
 * Tested up to: 4.3.1
 *
 * Text Domain: seriously-simple-podcasting
 * Domain Path: /lang/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'includes/ssp-functions.php' );
require_once( 'includes/class-ssp-admin.php' );
require_once( 'includes/class-ssp-frontend.php' );

global $ssp_admin, $ss_podcasting;
$ssp_admin = new SSP_Admin( __FILE__, '1.11.2' );
$ss_podcasting = new SSP_Frontend( __FILE__, '1.11.2' );

if ( is_admin() ) {
	global $ssp_settings;
	require_once( 'includes/class-ssp-settings.php' );
	$ssp_settings = new SSP_Settings( __FILE__ );
}
