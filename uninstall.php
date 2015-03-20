<?php

/**
 * 
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean-up unused meta, options, etc. in the database.
 *
 */

// If plugin is not being uninstalled, exit (do nothing)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		delete_option('lazyCbox_lazy_delay');
		delete_option('lazyCbox_enqueue_js');
		delete_option(	'lazyCbox_enqueue_css');
		delete_option(	'lazyCbox_use_colorbox');
		delete_option(	'lazyCbox_use_placeholder');
		delete_option(	'lazyCbox_max_width' );
		delete_option(	'lazyCbox_max_height');

	exit;
}

// Do something here if plugin is being uninstalled.
