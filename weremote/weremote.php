<?php

/**
 *
 * The plugin bootstrap file
 *
 * This file is responsible for starting the plugin using the main plugin class file.
 *
 * @since 0.0.1
 * @package We_Remote
 *
 * @wordpress-plugin
 * Plugin Name:     We Remote
 * Description:     Link Validator & Endpoint
 * Version:         0.0.1
 * Author:          Gabriel Ceschini
 * Author URI:      https://www.linkedin.com/in/gceschini/
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     we-remote
 * Domain Path:     /lang
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not permitted.' );
}

if ( ! class_exists( 'we_remote' ) ) {

	/*
	 * main we_remote class
	 *
	 * @class we_remote
	 * @since 0.0.1
	 */
	class we_remote {

		/*
		 * we_remote plugin version
		 *
		 * @var string
		 */
		public $version = '0.0.1';

		/**
		 * The single instance of the class.
		 *
		 * @var we_remote
		 * @since 0.0.1
		 */
		protected static $instance = null;

		/**
		 * Main we_remote instance.
		 *
		 * @since 0.0.1
		 * @static
		 * @return we_remote - main instance.
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * we_remote class constructor.
		 */
		public function __construct() {
			$this->load_plugin_textdomain();
			$this->define_constants();
			$this->includes();
			$this->define_actions();
		}

		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'we-remote', false, basename( dirname( __FILE__ ) ) . '/lang/' );
		}

		/**
		 * Include required core files
		 */
		public function includes() {
			// Load custom functions and hooks
			require_once __DIR__ . '/includes/includes.php';
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}


		/**
		 * Define we_remote constants
		 */
		private function define_constants() {
			define( 'WE_REMOTE_PLUGIN_FILE', __FILE__ );
			define( 'WE_REMOTE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			define( 'WE_REMOTE_VERSION', $this->version );
			define( 'WE_REMOTE_PATH', $this->plugin_path() );
		}

		/**
		 * Define we_remote actions
		 */
		public function define_actions() {
			//
			add_action( 'admin_enqueue_scripts', array( $this, 'we_remote_enqueue_scripts' ) );
			add_action( 'admin_menu', array( $this, 'define_menus' ) );
			add_action( 'admin_init', array( 'WR_Settings', 'init_settings' ) );
			add_action( 'admin_init', array( 'WR_Settings', 'init_fields' ) );
		}

		/**
		 * Define we_remote menus
		 */
		public function define_menus() {
            // Add We Remote Menu
			add_menu_page(
				'We Remote',
				'We Remote',   
				'manage_options',
			    'we-remote',
			    [ $this , 'we_remote_page' ],
				'dashicons-editor-unlink'
			);

			// Add LinkValitador Submenu
			add_submenu_page(
			    'we-remote',
			    'Link Validator',
			    'Link Validator',
			    'manage_options',
			    'link-validator',
			    [ 'LinkValidator' , 'link_validator_page' ]
			);

			// Add Settings Submenu			
			add_submenu_page(
			    'we-remote',
			    'Settings',
			    'Settings',
			    'manage_options',
			    'wr-settings',
				[ 'WR_Settings' , 'settings_page' ]
			);
		}

		/**
		 * Define we_remote menus
		 */
		public function we_remote_page() {
			?>
			<div class="wrap">
				<h1>We Remote Plugin</h1>
				<p>This plugin corresponds to the We Remote technical test for WordPress Development.</p>
				<p>You can find more information about the test: <a href="https://mcontigo.notion.site/WordPress-Development-0ab955afeefa428c9b25b74c221f2f46" target="_blank">here</a></p>
				<p>API Documentation: <a href="<?php echo plugin_dir_url(__FILE__) ?>swagger.yaml" download>swagger.yaml</a></p>
			</div>
			<?php
		}

		/**
		 * Enqueue CSS and JS files
		 */
		public function we_remote_enqueue_scripts() {
			// Enqueue CSS
			wp_enqueue_style('link-validator-styles', plugin_dir_url(__FILE__) . 'assets/css/linkvalidator.css', array(), '1.0.0');

			// Enqueue JS
			wp_enqueue_script('link-validator-scripts', plugin_dir_url(__FILE__) . 'assets/js/linkvalidator.js', array('jquery'), '1.0.0', true);

			// Font Awesome
			wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css', array(), '5.15.3');
		}
	}

	$we_remote = new we_remote();
}
