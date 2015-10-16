<?php
/**
 * Plugin Name:  Awesome Forms
 * Plugin URI:   http://www.awesome.ug
 * Description:  Drag & drop your Form with the Awesome Forms Plugin.
 * Version:      1.0.0 beta 20
 * Author:       awesome.ug
 * Author URI:   http://www.awesome.ug
 * Author Email: contact@awesome.ug
 * License:      GPLv3.0
 * License URI: ./assets/license.txt
 * Text Domain: af-locale
 * Domain Path: /languages
 */

if( !defined( 'ABSPATH' ) )
{
	exit;
}

class AF_Init
{

	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function init()
	{
		global $af_plugin_errors;

		$af_plugin_errors = array();

		// Loading variables
		self::constants();
		self::load_textdomain();

		// Loading other stuff
		self::includes();

		// Install & Uninstall Scripts
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		if( !self::is_installed() )
		{
			add_action( 'init', array( __CLASS__, 'install_plugin' ) );
		}

		// Functions on Frontend
		if( is_admin() ):
			// Register admin styles and scripts
			add_action( 'plugins_loaded', array( __CLASS__, 'check_requirements' ) );
			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		else:
			// Register plugin styles and scripts
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_plugin_styles' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_plugin_scripts' ) );
		endif;
	}

	/**
	 * Defining Constants for Use in Plugin
	 *
	 * @since 1.0.0
	 */
	public static function constants()
	{
		define( 'AF_FOLDER', self::get_folder() );
		define( 'AF_RELATIVE_FOLDER', substr( AF_FOLDER, strlen( WP_PLUGIN_DIR ), strlen( AF_FOLDER ) ) );
		define( 'AF_URLPATH', self::get_url_path() );

		define( 'AF_COMPONENTFOLDER', AF_FOLDER . 'components/' );
	}

	/**
	 * Getting Folder
	 *
	 * @since 1.0.0
	 */
	private static function get_folder()
	{
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Getting URL
	 *
	 * @since 1.0.0
	 */
	private static function get_url_path()
	{
		$slashed_folder = str_replace( '\\', '/', AF_FOLDER ); // Replacing backslashes width slashes vor windows installations
		$sub_path = substr( $slashed_folder, strlen( ABSPATH ), ( strlen( $slashed_folder ) - 11 ) );
		$script_url = get_bloginfo( 'wpurl' ) . '/' . $sub_path;

		return $script_url;
	}

	/**
	 * Loads the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public static function load_textdomain()
	{
		load_plugin_textdomain( 'af-locale', FALSE, AF_RELATIVE_FOLDER . '/languages' );
	}

	/**
	 * Getting include files
	 *
	 * @since 1.0.0
	 */
	public static function includes()
	{
		// Loading Functions
		include( AF_FOLDER . 'functions.php' );
		include( AF_FOLDER . 'includes/wp-editor.php' );

		// Loading Core
		include( AF_FOLDER . 'core/init.php' );

		include( AF_COMPONENTFOLDER . 'response-handlers/component.php' );
		include( AF_COMPONENTFOLDER . 'restrictions/component.php' );
		include( AF_COMPONENTFOLDER . 'result-handlers/component.php' );
	}

	/**
	 * Is plugin already installed?
	 */
	public static function is_installed()
	{
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'questions_questions',
			$wpdb->prefix . 'questions_answers',
			$wpdb->prefix . 'questions_responds',
			$wpdb->prefix . 'questions_respond_answers',
			$wpdb->prefix . 'questions_settings',
			$wpdb->prefix . 'form_participiants'
		);

		// Checking if all tables are existing
		$not_found = FALSE;
		foreach( $tables AS $table ):
			if( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ):
				$not_found = TRUE;
			endif;
		endforeach;

		$is_installed_option = (boolean) get_option( 'questions_is_installed', FALSE );

		if( $not_found || FALSE == $is_installed_option )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Checking Requirements and adding Error Messages.
	 *
	 * @since 1.0.0
	 */
	public static function check_requirements()
	{
		global $af_plugin_errors;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is
	 *                                 disabled or plugin is activated on an individual blog
	 *
	 * @since 1.0.0
	 */
	public static function activate( $network_wide )
	{
		global $wpdb;

		self::install_tables();
	}

	/**
	 * Creating / Updating tables
	 */
	public static function install_tables()
	{
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$table_elements = $wpdb->prefix . 'questions_questions';
		$table_answers = $wpdb->prefix . 'questions_answers';
		$table_responds = $wpdb->prefix . 'questions_responds';
		$table_respond_answers = $wpdb->prefix . 'questions_respond_answers';
		$table_settings = $wpdb->prefix . 'questions_settings';
		$table_participiants = $wpdb->prefix . 'questions_participiants';
		$table_email_notifications = $wpdb->prefix . 'questions_email_notifications';

		$sql = "CREATE TABLE $table_elements (
			id int(11) NOT NULL AUTO_INCREMENT,
			questions_id int(11) NOT NULL,
			question text NOT NULL,
			sort int(11) NOT NULL,
			type char(50) NOT NULL,
			UNIQUE KEY id (id)
			) ENGINE = INNODB DEFAULT CHARSET = utf8;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_answers (
			id int(11) NOT NULL AUTO_INCREMENT,
			question_id int(11) NOT NULL,
			section char(100) NOT NULL,
			answer text NOT NULL,
			sort int(11) NOT NULL,
			UNIQUE KEY id (id)
			) ENGINE = INNODB DEFAULT CHARSET = utf8;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_responds (
			id int(11) NOT NULL AUTO_INCREMENT,
			questions_id int(11) NOT NULL,
			user_id int(11) NOT NULL,
			timestamp int(11) NOT NULL,
			remote_addr char(15) NOT NULL,
			cookie_key char(50) NOT NULL,
			UNIQUE KEY id (id)
			) ENGINE = INNODB DEFAULT CHARSET = utf8;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_respond_answers (
			id int(11) NOT NULL AUTO_INCREMENT,
			respond_id int(11) NOT NULL,
			question_id int(11) NOT NULL,
			value text NOT NULL,
			UNIQUE KEY id (id)
			) ENGINE = INNODB DEFAULT CHARSET = utf8;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_settings (
			id int(11) NOT NULL AUTO_INCREMENT,
			question_id int(11) NOT NULL,
			name text NOT NULL,
			value text NOT NULL,
			UNIQUE KEY id (id)
			) ENGINE = INNODB DEFAULT CHARSET = utf8;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_participiants (
			id int(11) NOT NULL AUTO_INCREMENT,
			survey_id int(11) NOT NULL,
			user_id int(11) NOT NULL,
			UNIQUE KEY id (id)
			) ENGINE = INNODB DEFAULT CHARSET = utf8;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_email_notifications (
			id int(11) NOT NULL AUTO_INCREMENT,
			form_id int(11) NOT NULL,
			notification_name text NOT NULL,
			from_name text NOT NULL,
			from_email text NOT NULL,
			to_name text NOT NULL,
			to_email text NOT NULL,
			subject text NOT NULL,
			message text NOT NULL,
			UNIQUE KEY id (id)
			) ENGINE = INNODB DEFAULT CHARSET = utf8;";

		dbDelta( $sql );

		$sql = "ALTER TABLE $table_elements CONVERT TO CHARACTER SET utf8 collate utf8_general_ci;";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE $table_answers CONVERT TO CHARACTER SET utf8 collate utf8_general_ci;";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE $table_responds CONVERT TO CHARACTER SET utf8 collate utf8_general_ci;";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE $table_respond_answers CONVERT TO CHARACTER SET utf8 collate utf8_general_ci;";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE $table_participiants CONVERT TO CHARACTER SET utf8 collate utf8_general_ci;";
		$wpdb->query( $sql );

		$sql = "ALTER TABLE $table_settings CONVERT TO CHARACTER SET utf8 collate utf8_general_ci;";
		$wpdb->query( $sql );

		update_option( 'questions_db_version', '1.1.1' );
	}

	/**
	 * Installing plugin
	 */
	public static function install_plugin()
	{
		self::install_tables();
		flush_rewrite_rules();
		update_option( 'questions_is_installed', TRUE );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is
	 *                                 disabled or plugin is activated on an individual blog
	 */
	public static function deactivate( $network_wide )
	{
		delete_option( 'questions_is_installed' );
	}

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is
	 *                                 disabled or plugin is activated on an individual blog
	 *
	 * @since 1.0.0
	 */
	public static function uninstall( $network_wide )
	{
	}

	/**
	 * Registers and enqueues plugin-specific styles.
	 *
	 * @since 1.0.0
	 */
	public static function register_plugin_styles()
	{
		wp_enqueue_style( 'af-plugin-styles', AF_URLPATH . '/includes/css/display.css' );
	}

	/**
	 * Registers and enqueues plugin-specific scripts.
	 *
	 * @since 1.0.0
	 */
	public static function register_plugin_scripts()
	{
	}

	/**
	 * Showing Errors
	 *
	 * @since 1.0.0
	 */
	public static function admin_notices()
	{
		global $af_plugin_errors, $af_plugin_errors;

		if( count( $af_plugin_errors ) > 0 ):
			foreach( $af_plugin_errors AS $error )
			{
				echo '<div class="error"><p>' . $error . '</p></div>';
			}
		endif;

		if( count( $af_plugin_errors ) > 0 ):
			foreach( $af_plugin_errors AS $notice )
			{
				echo '<div class="updated"><p>' . $notice . '</p></div>';
			}
		endif;
	}

}

AF_Init::init(); // Starting immediately!
