<?php
/*
 * Plugin Name: colorbox-gallery
 * Version: 1.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: colorbox-gallery
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-colorbox-gallery.php' );
require_once( 'includes/class-colorbox-gallery-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-colorbox-gallery-admin-api.php' );
require_once( 'includes/lib/class-colorbox-gallery-post-type.php' );
require_once( 'includes/lib/class-colorbox-gallery-taxonomy.php' );

/**
 * Returns the main instance of colorbox-gallery to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object colorbox-gallery
 */
function colorbox-gallery () {
	$instance = colorbox-gallery::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = colorbox-gallery_Settings::instance( $instance );
	}

	return $instance;
}

colorbox-gallery();