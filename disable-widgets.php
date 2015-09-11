<?php
/**
 * This plugin was based on Disable Widgets by [Zaantar](http://zaantar.eu)
 * @url https://wordpress.org/plugins/disable-widgets/
 *
 * @package   Disable Widgets
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      http://connections-pro.com
 * @copyright 2014 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Disable Widgets
 * Plugin URI:        http://connections-pro.com
 * Description:       Disable widgets.
 * Version:           2.0
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       disable_widgets
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists('Disable_Widgets') ) {

	class Disable_Widgets {

		const VERSION = 1.1;

		private $widgets = array();

		public function __construct() {

			add_action( 'init', array( $this, 'load_textdomain' ) );

			// Settings API.
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// Add the menu to the Settings menu.
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			// Get the registered widgets. This must come before unregistering them.
			// NOTE: This action has to run before priority 100.
			add_action( 'widgets_init', array( $this, 'get_widgets' ), 99.98 );

			// Unregister the disabled widgets.
			// NOTE: This action has to run before priority 100.
			add_action( 'widgets_init', array( $this, 'unregister_widgets' ), 99.99 );
		}

		public function load_textdomain() {

			load_plugin_textdomain( 'disable-widgets', false, basename( dirname(__FILE__) ).'/languages' );
		}

		public function register_settings() {

			register_setting( 'disable-widgets', 'disabled-widgets', array( $this, 'validate_settings' ) );
		}

		/**
		 * NOTE: Per Codex this will actually be run twice.
		 * @url http://codex.wordpress.org/Function_Reference/register_setting#Notes
		 * @param  array
		 * @return array
		 */
		public function validate_settings( $value ) {

			// Ensure only registered widgets can be disabled.
			foreach ( $value as $widget ) {

				if ( ! array_key_exists( $widget, $this->widgets ) ) unset( $value[ $widget ] );
			}

			return $value;
		}

		public function admin_menu() {

			$slug = is_multisite() ? 'settings.php' : 'options-general.php';

			$capability = is_multisite() ? 'manage_network_options' : 'manage_options';

			add_submenu_page(
				$slug,
				__( 'Disable Widgets', 'disable_widgets' ),
				__( 'Disable Widgets', 'disable_widgets' ),
				$capability,
				'disable-widgets',
				array( $this, 'settings_page' )
				);
		}

		public function get_widgets() {
			global $wp_widget_factory;

			$widgets = $wp_widget_factory->widgets;

			usort( $widgets, array( $this, '_sort_name_callback' ) );

			foreach ( $widgets as $widget ) {

				$className = get_class( $widget );
				$widgetName = $widget->name;

				$this->widgets[ $className ] = $widgetName;
			}

		}

		public function _sort_name_callback( $a, $b ) {

			return strnatcasecmp( $a->name, $b->name );
		}

		public function get_options() {

			$defaults = array();

			return wp_parse_args( get_site_option( 'disabled-widgets', array() ), $defaults );
		}


		public function settings_page() {

			$disabled = $this->get_options();

			?>
				<div class="wrap">
					<h2><?php _e( 'Disable Widgets', 'disable_widgets' ); ?></h2>

					<form name="disable-widgets" method="post" action="options.php">
						<input type="hidden" name="action" value="disable-widgets">
						<?php wp_nonce_field( 'disable-widgets-nonce' ); ?>
						<table class="form-table">

							<tr valign="top">
								<th>
									<label><?php _e( 'Available Widgets ' ); ?></label>
								</th>
								<td>
									<ul>
									<?php
										foreach( $this->widgets as $widget => $name ) {

											$set        = array_key_exists( $widget, $disabled );
											$className  = "disabled-widgets[$widget]";
											?>
												<li><input type="checkbox" name="<?php echo esc_attr( $className ); ?>" id="<?php echo esc_attr( $className ); ?>" <?php checked( $set ); ?> />
												<label for="<?php echo $className; ?>"><?php _e( $name ); ?></label></li>
											<?php

											if ( $set ) {

												$aks = array_keys( $disabled, $widget );

												foreach ( $aks as $ak ) {

													unset( $disabled[ $ak ] );
												}
											}
										}
									?>
									</ul>
								</td>
							</tr>

						</table>

						<?php settings_fields( 'disable-widgets' ); ?>

						<p class="submit">
							<input name="Submit" type="submit" class="button-primary" value="<?php _e( 'Update Settings' ); ?>" />
						</p>
					</form>
				</div>
			<?php
		}


		public function unregister_widgets() {

			$widgets = array_keys( $this->get_options() );

			foreach ( $widgets as $widget ) {

				unregister_widget( $widget );
			}
		}

	}

	/* Fire it up! */
	new Disable_Widgets;
}
