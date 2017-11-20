<?php
/**
 * The user interface and activation/deactivation methods for administering
 * the WP Contents Update Notification plugin
 *
 * @package wp-ryver-notification
 * @link http://wordpress.org/extend/plugins/wp-ryver-notification/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author
 * @copyright
 */

class ryver_notification_admin extends ryver_notification {
	/**
	 * The WP privilege level required to use the admin interface
	 * @var string
	 */
	protected $capability_required;

	/**
	 * Metadata and labels for each element of the plugin's options
	 * @var array
	 */
	protected $fields;

	/**
	 * URI for the forms' action attributes
	 * @var string
	 */
	protected $form_action;

	/**
	 * Name of the page holding the options
	 * @var string
	 */
	protected $page_options;

	/**
	 * Metadata and labels for each settings page section
	 * @var array
	 */
	protected $settings;

	/**
	 * Title for the plugin's settings page
	 * @var string
	 */
	protected $text_settings;


	/**
	 * Sets the object's properties and options
	 *
	 * @return void
	 *
	 * @uses ryver_notification::initialize()  to set the object's
	 *	     properties
	 * @uses ryver_notification_admin::set_sections()  to populate the
	 *       $sections property
	 * @uses ryver_notification_admin::set_fields()  to populate the
	 *       $fields property
	 */
	public function __construct() {
		$this->initialize();

		// Translation already in WP combined with plugin's name.
		$this->text_settings = self::NAME . ' ' . __('Settings');

		if (is_multisite()) {
			$this->capability_required = 'manage_network_options';
			$this->form_action = '../options.php';
			$this->page_options = 'settings.php';
		} else {
			$this->capability_required = 'manage_options';
			$this->form_action = 'options.php';
			$this->page_options = 'options-general.php';
		}
	}

	/**
	 * Establishes the tables and settings when the plugin is activated
	 * @return void
	 */
	public function activate() {
		global $wpdb;

		if (is_multisite() && !is_network_admin()) {
			die($this->hsc_utf8(sprintf(__("%s must be activated via the Network Admin interface when WordPress is in multistie network mode.", 'wp-ryver-notification'), self::NAME)));
		}

		/*
		 * Create or alter the plugin's tables as needed.
		 */

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/*
		 * Save this plugin's options to the database.
		 */

		if (is_multisite()) {
			switch_to_blog(1);
		}

		// update_option($this->option_name, $this->options);
		if (is_multisite()) {
			restore_current_blog();
		}
	}

	/**
	 * Removes the tables and settings when the plugin is deactivated
	 * if the deactivate_deletes_data option is turned on
	 * @return void
	 */
	public function deactivate() {


	}

	/**
	 * Declares the callbacks for rendering and watch for any plugin was activated
	 *
	 */
	public function detect_plugin_activation($plugin, $network_activation) {
		$this->sendMessageToWebHook("```php".$plugin." plugin``` was **ACTIVATED**");
	}

	/**
	 * Declares the callbacks for rendering and watch for any plugin was deactivated
	 *
	 */
	public function detect_plugin_deactivation($plugin, $network_activation) {
		$this->sendMessageToWebHook("```php".$plugin." plugin``` was **DEACTIVATED**");
	}

	/**
	 * Declares the callbacks for theme switch hook
	 *
	 */
	public function detect_oldtheme_switch($oldname, $oldtheme=false) {
		$this->sendMessageToWebHook("```php".$oldtheme." theme``` was **DEACTIVATED**");
	}

	/**
	 * Declares the callbacks for theme switch hook
	 *
	 */
	public function detect_newtheme_switch($newname, $newtheme) {
		$this->sendMessageToWebHook("```php".$newname." theme``` was **ACTIVATED**");
	}

	/**
	 * Declares a menu item and callback for this plugin's settings page
	 *
	 * NOTE: This method is automatically called by WordPress when
	 * any admin page is rendered
	 */
	public function admin_menu() {
		add_submenu_page(
			$this->page_options,
			$this->text_settings,
			self::NAME,
			$this->capability_required,
			self::ID,
			array(&$this, 'page_settings')
		);
	}
	/**
	* function to send message to ryver
	*/
	private function sendMessageToWebHook($message) {
		$serverPath = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$message = "[[".strtoupper($_SERVER['HTTP_HOST'])."]](".$serverPath.") ".$message;
		$ryver = get_option('RYVER_WEBHOOK');

		if($ryver) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $ryver);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"body\": \"".$message."\" }");
			curl_setopt($ch, CURLOPT_POST, 1);

			$headers = array();
			$headers[] = "Content-Type: application/json; charset=utf-8";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
			    echo 'Error:' . curl_error($ch);
			}
			curl_close ($ch);
		}
	}

	/**
	 * The callback for rendering the settings page
	 * @return void
	 */
	public function page_settings() {

		if (is_multisite()) {
			include_once ABSPATH . 'wp-admin/options-head.php';
		}

		if(isset($_POST['ryver_webhook']) && !empty($_POST['ryver_webhook'])) {
			$option_name = 'RYVER_WEBHOOK' ;
			$new_value = $_POST['ryver_webhook'] ;

			if ( get_option( $option_name ) !== false ) {
			    // The option already exists, so we just update it.
			    update_option( $option_name, $new_value );
			} else {
			    // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
			    $deprecated = null;
			    $autoload = 'no';
			    add_option( $option_name, $new_value, $deprecated, $autoload );
			}
		}
		// echo '<h2>' . $this->hsc_utf8($this->text_settings) . '</h2>';
		// echo '<form action="' . $this->hsc_utf8($this->form_action) . '" method="post">' . "\n";
		include 'setting.php';
		// echo '</form';
	}
}
