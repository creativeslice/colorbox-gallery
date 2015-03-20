<?php
/*
 * Plugin Name: Lazy Colorbox
 * Version: 1.0
 * Plugin URI: http://www.joinerylabs.com/wordpress/lazy-colorbox
 * Description: Lazy Load your images 
 * Author: Trip Grass
 * Author URI: http://www.joinerylabs.com/about/tripgrass
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: lazy-colorbox
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Trip Grass
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

//register_activation_hook( __FILE__, array('Lazy_Colorbox', 'plugin_activated' ));

// Load plugin class files
require_once( 'includes/class-lazy-colorbox.php' );
require_once( 'includes/class-lazy-colorbox-settings.php' );

// Load plugin libraries
if(is_admin()){
	require_once( 'includes/lib/class-lazy-colorbox-admin-api.php' );
}
else{
	require_once( 'includes/lib/class-lazy-colorbox-loader-api.php' );
}

/**
 * Returns the main instance of Lazy_Colorbox to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Lazy_Colorbox
 */
function Lazy_Colorbox ( $args = array() ) {
	return Lazy_Colorbox::instance( $args ,  __FILE__, '1.0.0' );
}

$args = array('types'=>array('this', 'that'));
$lazy_colorbox = Lazy_Colorbox( $args );
