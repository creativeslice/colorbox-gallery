<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Class for Front End Work
class Lazy_Colorbox_Loader_API {

	/**
	 * Constructor function
	 */
	public function __construct ( $parent ) {
		$images = $parent->images;
		add_filter( 'the_content', array( __CLASS__, 'setup_lazy_src'), 99 ); // run this later, so other content filters have run, including image_add_wh on WP.com
		add_filter( 'post_thumbnail_html', array( __CLASS__, 'setup_lazy_src' ), 11 );
		add_filter( 'get_avatar', array( __CLASS__, 'setup_lazy_src' ), 11 );	
	
	}


	/**
	 * Filters wordpress output and converts src to data-lazy-cbox .
	 *
	 * @since 1.0.0
	 * @static
	 * @return html elements converted to lazy-colorbox targets 
	 */
	public static function replace_callback( $m ) {
		if( $path = get_option('lazyCbox_placeholder_path' ) ){
			$placeholder_path = $path;
		}
		else{
			$placeholder_path ="";
			//$placeholder_path = dirname( __FILE__ ) . "/assets/img/1x1.trans.gif";
		}	
		$cbox_href = "";
		preg_match ( '/-([0-9]+)x/', $m[2], $width );
		preg_match ( '/x([0-9]+)./', $m[2], $height );
		if( count($width) > 0 && count($height)>0 ){
			$pattern = "/-".$width[1]."x".$height[1]."/";
			$cbox_href = "data-lazy-cbox-anchor='" . preg_replace( $pattern , '' , $m[2] ) . "'";
		}
		return '<img ' . $m[1] . ' src="'.$placeholder_path.'" '. $cbox_href .' data-lazy-cbox="' . $m[2] . '" ' . $m[3] . '><noscript><img '. $m[1] . ' src="' . $m[2] .'" '. $m[3] . '></noscript>';
	}

	/**
	 * Filters wordpress output and converts src to data-lazy-cbox .
	 *
	 * @since 1.0.0
	 * @static
	 * @return html elements converted to lazy-colorbox targets 
	 */
	public static function setup_lazy_src( $content ) {
		$loadAssets = 0;
		// get options: no access to class
		$placeholder = get_option('lazyCbox_use_placeholder' );

		// Don't lazyload for feeds, previews, mobile MAKE AN ACF OPTION
		if( is_feed() || is_preview() || ( function_exists( 'is_mobile' ) && is_mobile() ) )
			return;

		// Don't lazy-load if the content has already been run through previously	
		if ( false !== strpos( $content, 'data-lazy-cbox' ) )
			return $content;
		
		if( $placeholder == 'on' ){
			// $loadAssets is true is data-lazy-cbox exist and will load assets only on pages that require it.
			$loadAssets = preg_match( '#<img([^>]+?)src=[\'"]?([^\'"\s>]+)[\'"]?([^>]*)>#', $content );
			$content = preg_replace_callback( '#<img([^>]+?)src=[\'"]?([^\'"\s>]+)[\'"]?([^>]*)>#', array( __CLASS__ ,'replace_callback'), $content );
		}		
		else{
			$content = preg_replace( '#<img([^>]+?)src=[\'"]?([^\'"\s>]+)[\'"]?([^>]*)>#', sprintf( '<img${1} data-lazy-cbox="${2}"${3}><noscript><img${1}src="${2}"${3}></noscript>' ), $content );
		}
		if ( $loadAssets ) {
			// if script or style is not registered, nothing happens
			wp_enqueue_script( 'lazy_colorbox-frontend' );
			wp_enqueue_style( 'lazy_colorbox-frontend' );
			wp_enqueue_script( 'lazy_colorbox-colorbox' );
			wp_enqueue_style( 'lazy_colorbox-colorbox' );			
		}
		return $content;
	}


	static function get_url( $path = '' ) {
		return plugins_url( ltrim( $path, '/' ), __FILE__ );
	}
}