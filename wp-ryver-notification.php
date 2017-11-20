<?php
/**
 * Plugin Name: WP Ryver Notification
 *
 * Description: Watch the plugins & themes update, then notification to your team via Ryver
 *
 * Plugin URI:
 * Version: 1.0
 * Author: Truong Vu
 * License:
 * @package wp-ryver-notification
 *
 */
/**
 * The instantiated version of this plugin's class
 */
$GLOBALS['ryver_notification'] = new ryver_notification;

// define('RYVER_WEBHOOK', 'https://phapsu.ryver.com/application/webhook/BTeTOsAB0FpKmN0');

/**
 * WP Contents Update Notification
 *
 * @package wp-ryver-notification
 * @link http://wordpress.org/extend/plugins/wp-ryver-notification/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author
 * @copyright
 */
class ryver_notification {
	/**
	 * This plugin's identifier
	 */
	const ID = 'wp-ryver-notification';

	/**
	 * This plugin's name
	 */
	const NAME = 'Ryver Notification';

	/**
	 * This plugin's version
	 */
	const VERSION = '1.0';

	/**
	 * This plugin's table name prefix
	 * @var string
	 */
	protected $prefix = 'ryver_notification_';


	/**
	 * Has the internationalization text domain been loaded?
	 * @var bool
	 */
	protected $loaded_textdomain = false;

	/**
	 * This plugin's options
	 *
	 * Options from the database are merged on top of the default options.
	 *
	 * @see ryver_notification::set_options()  to obtain the saved
	 *      settings
	 * @var array
	 */
	protected $options = array();

	/**
	 * This plugin's default options
	 * @var array
	 */
	protected $options_default = array(
		'deactivate_deletes_data' => 1,
		'example_int' => 5,
		'example_string' => '',
		'track_logins' => 1,
	);

	/**
	 * Our option name for storing the plugin's settings
	 * @var string
	 */
	protected $option_name;

	/**
	 * Name, with $table_prefix, of the table tracking login failures
	 * @var string
	 */
	protected $table_login;

	/**
	 * Our usermeta key for tracking when a user logged in
	 * @var string
	 */
	protected $umk_login_time;


	/**
	 * Declares the WordPress action and filter callbacks
	 *
	 * @return void
	 * @uses ryver_notification::initialize()  to set the object's
	 *       properties
	 */
	public function __construct() {
		$this->initialize();

		if (is_admin()) {
			$this->load_plugin_textdomain();

			require_once dirname(__FILE__) . '/admin.php';
			$admin = new ryver_notification_admin;

			if (is_multisite()) {
				$admin_menu = 'network_admin_menu';
				$admin_notices = 'network_admin_notices';
			} else {
				$admin_menu = 'admin_menu';
				$admin_notices = 'admin_notices';
			}

			add_action($admin_menu, array(&$admin, 'admin_menu'));
			add_action('activated_plugin', array(&$admin, 'detect_plugin_activation'), 10, 2 );
			add_action('deactivated_plugin', array(&$admin, 'detect_plugin_deactivation'), 10, 2 );
			add_action("after_switch_theme", array(&$admin, 'detect_oldtheme_switch'), 10 ,  2);
			add_action("switch_theme", array(&$admin, 'detect_newtheme_switch'), 10 ,  2);

			register_activation_hook(__FILE__, array(&$admin, 'activate'));
			if ($this->options['deactivate_deletes_data']) {
				register_deactivation_hook(__FILE__, array(&$admin, 'deactivate'));
			}
		}
	}

	/**
	 * Sets the object's properties and options
	 *
	 * This is separated out from the constructor to avoid undesirable
	 * recursion.  The constructor sometimes instantiates the admin class,
	 * which is a child of this class.  So this method permits both the
	 * parent and child classes access to the settings and properties.
	 *
	 * @return void
	 *
	 * @uses ryver_notification::set_options()  to replace the default
	 *       options with those stored in the database
	 */
	protected function initialize() {
		$this->option_name = self::ID . '-options';
		$this->umk_login_time = self::ID . '-login-time';

		$this->set_options();
	}

	/**
	 * A centralized way to load the plugin's textdomain for
	 * internationalization
	 * @return void
	 */
	protected function load_plugin_textdomain() {
		if (!$this->loaded_textdomain) {
			load_plugin_textdomain(self::ID, false, self::ID . '/languages');
			$this->loaded_textdomain = true;
		}
	}

	/**
	 * Sanitizes output via htmlspecialchars() using UTF-8 encoding
	 *
	 * Makes this program's native text and translated/localized strings
	 * safe for displaying in browsers.
	 *
	 * @param string $in   the string to sanitize
	 * @return string  the sanitized string
	 */
	protected function hsc_utf8($in) {
		return htmlspecialchars($in, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Replaces the default option values with those stored in the database
	 * @uses login_security_solution::$options  to hold the data
	 */
	protected function set_options() {
		if (is_multisite()) {
			switch_to_blog(1);
			$options = get_option($this->option_name);
			restore_current_blog();
		} else {
			$options = get_option($this->option_name);
		}
		if (!is_array($options)) {
			$options = array();
		}
		$this->options = array_merge($this->options_default, $options);
	}
}
