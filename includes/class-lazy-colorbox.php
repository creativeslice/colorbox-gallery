<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Lazy_Colorbox {

	/**
	 * The single instance of Lazy_Colorbox.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * jquery vars to output to html
	 * @var 	object
	 * @access  
	 * @since 	1.0.0
	 */
	protected static $jquery_vars = "";

	/**
	 * image sizes to output to html
	 * @var 	object
	 * @access  
	 * @since 	1.0.0
	 */
	protected static $image_array = "";

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $args , $file = '', $version = '1.0.0' ) {
		// wordpress option name prefix:
		$this->base = 'lazyCbox_';

		$this->_version = $version;
		$this->_token = 'lazy_colorbox';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );

		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Setup Options
        $defaults = array(
            'types' => array('default')
        );
        $args = wp_parse_args( $args, $defaults );
        foreach ( $defaults as $key => $value )
        {
            if (! is_array($args[$key]) )
            {
                $args[$key] = explode(',', $args[$key]);
            }
        }
        extract( $args, EXTR_SKIP );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		//	Get options for plugin
		$this->settings = $this->settings_fields();

		// Load API for generic admin functions
		if ( is_admin() ) {
			// check if is just activated
			if( !get_option( $this->_token . '_version') ){
		//		self::$database_build = $this->settings_fields();
			}
			else{
				$this->settings = Lazy_Colorbox_Settings::instance( $this );
				$this->admin = new Lazy_Colorbox_Admin_API();
			}
		}
		// Load Filters for wrapping images
		else{
			self::$jquery_vars = $this->get_option_values();
			$this->loader = new Lazy_Colorbox_Loader_API( $this );
			add_action ('wp_footer' , array(__CLASS__,'output_jquery_vars') );
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	public static function get_image_sizes(){
		$images = array();
		global $_wp_additional_image_sizes;
		foreach (get_intermediate_image_sizes() as $s) {
			if (isset($_wp_additional_image_sizes[$s])) {
				$width = intval($_wp_additional_image_sizes[$s]['width']);
				$height = intval($_wp_additional_image_sizes[$s]['height']);
			} else {
				$width = get_option($s.'_size_w');
				$height = get_option($s.'_size_h');
			}
			$images[$s] = array('width'=>$width, 'height'=>$height);
		}
		return $images;
	}

	public function lazy_acf( $image ){
//print_r($image);
//print_r( $this->settings);
if( $this->use_mobile_size() ){
	$mobile = $this->get_mobile_size();
	$desk = $this->get_desk_size();
	$break = $this->get_breakpoint();
}
		$html ="
                <a href='" . $image['url'] . "'>
                     <img width='". $image['sizes'][$desk . '-width'] ."' height='". $image['sizes'][$desk . '-height'] ."' data-lazy-cbox='" . $image['sizes'][$desk] . "' alt='" . $image['alt'] ."' />
                </a>";
		return $html;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		// obtained and saved all available media sizes (would need to reactivate plugin if sizes change)
		$this->images = get_option('lazyCbox_image_sizes');
		self::$image_array = $this->images;

		$image_selector = array('none'=>'Default');
		if( is_array($this->images) && count($this->images) > 0){
			foreach( $this->images as $image=>$sizes ){
				$image_selector[$image] = $image;
			}
		}

		$settings['options'] = array(
			'title'					=> __( 'Options', 'lazy-colorbox' ),
			'description'			=> __( 'Setup your image options here.', 'lazy-colorbox' ),
			'fields'				=> array(
				array(
					'id' 			=> 'lazy_delay',
					'label'			=> __( 'Lazy Loader Delay' , 'lazy-colorbox' ),
					'description'	=> __( 'Delay after document ready in milliseconds', 'lazy-colorbox' ),
					'type'			=> 'text',
					'default'		=> '10',
					'placeholder'	=> __( '500', 'lazy-colorbox' )
				),
				array(
					'id' 			=> 'enqueue_js',
					'label'			=> __( 'Load this Javascript', 'lazy-colorbox' ),
					'description'	=> __( 'Uncheck if you want to enqueue on your own', 'lazy-colorbox' ),
					'type'			=> 'checkbox',
					'default'		=> 'on'
				),
				array(
					'id' 			=> 'enqueue_css',
					'label'			=> __( 'Load this CSS', 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'checkbox',
					'default' 		=> 'on'
				),
				array(
					'id' 			=> 'use_colorbox',
					'label'			=> __( 'Include Colorbox', 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'checkbox',
					'default'		=> 'on'
				),
				array(
					'id' 			=> 'force_media_file',
					'label'			=> __( 'Force Media File Link', 'lazy-colorbox' ),
					'description'	=> __( 'If using Colorbox, this will force a Gallery Image\'s anchor wrap to link to the Media File', 'lazy-colorbox' ),
					'type'			=> 'checkbox',
					'default'		=> 'on'
				),
				array(
					'id' 			=> 'max_width',
					'label'			=> __( 'Colorbox Max Width' , 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'text',
					'default'		=> '60%',
					'placeholder'	=> __( '60%', 'lazy-colorbox' )
				),
				array(
					'id' 			=> 'max_height',
					'label'			=> __( 'Colorbox Max Height' , 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'text',
					'default'		=> '60%',
					'placeholder'	=> __( '60%', 'lazy-colorbox' )
				),
				array(
					'id' 			=> 'use_placeholder',
					'label'			=> __( 'Use the placeholder image', 'lazy-colorbox' ),
					'description'	=> __( 'Select to use a placeholder image.', 'lazy-colorbox' ),
					'type'			=> 'checkbox',
					'default'		=> 'on'
				),
				array(
					'id' 			=> 'placeholder_path',
					'label'			=> __( 'Path to Placeholder Image' , 'lazy-colorbox' ),
					'description'	=> __( 'url to img from template directory.', 'lazy-colorbox' ),
					'type'			=> 'text',
					'callback'		=> array( 'Lazy_Colorbox_Settings', 'validatePlaceholder'),
					'placeholder'	=> __( '/path/img.jpg', 'lazy-colorbox' )
				),
				array(
					'id' 			=> 'use_mobile_size',
					'label'			=> __( 'Use a Separate Size for Mobile', 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'checkbox',
					'default'		=> 'on'
				),
				array(
					'id' 			=> 'colorbox_size',
					'label'			=> __( 'Colorbox Size', 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'select',
					'options'		=> $image_selector,
					'default'		=> ''
				),
				array(
					'id' 			=> 'desk_size',
					'label'			=> __( 'Desktop Size', 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'select',
					'options'		=> $image_selector,
					'default'		=> ''
				),
				array(
					'id' 			=> 'mobile_size',
					'label'			=> __( 'Mobile Size', 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'select',
					'options'		=> $image_selector,
					'default'		=> ''
				),
				array(
					'id' 			=> 'mobile_breakpoint',
					'label'			=> __( 'Mobile Breakpoint' , 'lazy-colorbox' ),
					'description'	=> __( '', 'lazy-colorbox' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( '', 'lazy-colorbox' )
				),

/*				array(
					'id' 			=> 'text_field',
					'label'			=> __( 'Some Text' , 'lazy-colorbox' ),
					'description'	=> __( 'This is a standard text field.', 'lazy-colorbox' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Placeholder text', 'lazy-colorbox' )
				),
				array(
					'id' 			=> 'select_box',
					'label'			=> __( 'A Select Box', 'lazy-colorbox' ),
					'description'	=> __( 'A standard select box.', 'lazy-colorbox' ),
					'type'			=> 'select',
					'options'		=> array( 'drupal' => 'Drupal', 'joomla' => 'Joomla', 'wordpress' => 'WordPress' ),
					'default'		=> 'wordpress'
				),
				array(
					'id' 			=> 'radio_buttons',
					'label'			=> __( 'Some Options', 'lazy-colorbox' ),
					'description'	=> __( 'A standard set of radio buttons.', 'lazy-colorbox' ),
					'type'			=> 'radio',
					'options'		=> array( 'superman' => 'Superman', 'batman' => 'Batman', 'ironman' => 'Iron Man' ),
					'default'		=> 'batman'
				),
				array(
					'id' 			=> 'multiple_checkboxes',
					'label'			=> __( 'Some Items', 'lazy-colorbox' ),
					'description'	=> __( 'You can select multiple items and they will be stored as an array.', 'lazy-colorbox' ),
					'type'			=> 'checkbox_multi',
					'options'		=> array( 'square' => 'Square', 'circle' => 'Circle', 'rectangle' => 'Rectangle', 'triangle' => 'Triangle' ),
					'default'		=> array( 'circle', 'triangle' )
				)*/
			)
		);

		$settings = apply_filters( $this->_token . '_settings_fields', $settings );
		return $settings;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		if( $this->enqueue_css() ){
			// styles are enqueued in the loader-api after detecting if they're required
			if( $this->use_colorbox() ){
				wp_register_style( $this->_token . '-colorbox', esc_url( $this->assets_url ) . 'css/colorbox.css', array(), $this->_version );
			}
			wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		}
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		if( $this->enqueue_js() ){
			// scripts are enqueued in the loader-api after detecting if they're required
			if( $this->use_colorbox() ){
				wp_register_script( $this->_token . '-colorbox', esc_url( $this->assets_url ) . 'js/jquery.colorbox.js', array( 'jquery' ), $this->_version );
			}
			wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend.js', array( 'jquery' ), $this->_version );
		}
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'lazy-colorbox', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'lazy-colorbox';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main Lazy_Colorbox Instance
	 *
	 * Ensures only one instance of Lazy_Colorbox is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Lazy_Colorbox()
	 * @return Main Lazy_Colorbox instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
		$this->_log_defaults();
		
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

	/**
	 * Log the defaults
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_defaults () {
		// gets all available image sizes
		$images= $this->get_image_sizes();
		update_option('lazyCbox_image_sizes', $images);
		update_option('lazyCbox_lazy_delay', 10);
		update_option('lazyCbox_enqueue_js', 'on');
		update_option(	'lazyCbox_enqueue_css' , 'on');
		update_option(	'lazyCbox_use_colorbox', 'on');
		update_option(	'lazyCbox_force_media_file', 'on');
		update_option(	'lazyCbox_use_placeholder', 'on');
		update_option(	'lazyCbox_max_width' , '60%');
		update_option(	'lazyCbox_max_height', '60%');
	}

	/**
	 * Adds values to all options
	 * @return void
	 */
	private function get_option_values () {
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {
				foreach ( $data['fields'] as $key=>$field ) {
					$option_name = $this->base . $field['id'];
					$option = get_option( $option_name );
					$this->settings[$section]['fields'][$key]['value'] = $option;
					$jquery_vars[$field['id']] = $option; 
				}
			}
			return $jquery_vars;
		}
	}

	/**
	 * Check if should use placeholder
	 * @return void
	 */
	private function use_placeholder () {
		if( $this->get_option_value('use_placeholder') == 'on' ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Check if should use mobile size
	 * @return void
	 */
	private function use_mobile_size () {
		if( $this->get_option_value('use_mobile_size') == 'on' ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * return mobile size
	 * @return void
	 */
	private function get_mobile_size () {
		return $this->get_option_value('mobile_size');
	}

	/**
	 * return desk size
	 * @return void
	 */
	private function get_desk_size () {
		return $this->get_option_value('desk_size');
	}

	/**
	 * return breakpoint
	 * @return void
	 */
	private function get_breakpoint () {
		return $this->get_option_value('breakpoint');
	}

	/**
	 * Check if should include colorbox
	 * @return void
	 */
	private function use_colorbox () {
		if( $this->get_option_value('use_colorbox') == 'on' ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Check if should enqueue js
	 * @return void
	 */
	private function enqueue_js () {
		if( $this->get_option_value('enqueue_js') == 'on' ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Check if should enqueue css
	 * @return void
	 */
	private function enqueue_css () {
		if( $this->get_option_value('enqueue_css') == 'on' ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Return option value
	 * @return void
	 */
	private function get_option_value ( $id ) {
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {
				foreach ( $data['fields'] as $key=>$field ) {
					$option_name = $field['id'];
					if( $option_name == $id ){	
						return $field['value'];
					}
				}
			}
		}				
	}
	public static function output_jquery_vars(){
		echo "<script type='text/javascript'> var cbox_variables = " . json_encode( self::$jquery_vars ) . "; var cbox_images = " . json_encode( self::$image_array ) . "; </script>";
	}
}