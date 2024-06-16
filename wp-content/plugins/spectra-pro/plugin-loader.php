<?php
namespace SpectraPro;

use SpectraPro\Admin\License_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin Loader.
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class PluginLoader {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class class name.
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
			return;
		}

		$class_to_load = $class;

		$filename = preg_replace(
			[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
			[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
			$class_to_load
		);

		if ( is_string( $filename ) ) {
			$filename = strtolower( $filename );

			$file = SPECTRA_PRO_DIR . $filename . '.php';

			// if the file redable, include it.
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
		register_activation_hook( SPECTRA_PRO_FILE, array( $this, 'activation_reset' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugin_init' ) );
	}

	/**
	 * After Finish loading UAG Free, then loaded pro core functionality
	 *
	 * Hooked - uagb_core_loaded
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function on_plugin_init() {

		load_plugin_textdomain( 'spectra-pro', false, SPECTRA_PRO_DIR . '/languages' );

		if ( ! defined( 'UAGB_VER' ) ) {
			add_action( 'admin_notices', array( $this, 'spectra_pro_fail_load' ) );
			return;
		}

		if ( ! did_action( 'spectra_core_loaded' ) || ! version_compare( UAGB_VER, SPECTRA_CORE_REQUIRED_VER, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'spectra_pro_fail_load_out_of_date' ) );
			return;
		}

		if ( is_admin() ) {
			Core\Admin::init();
		}

		( new License_Handler() )->init();
		Core\Base::init();
		Core\Assets::init();
		BlocksConfig\Config::init();
		Core\Extensions_Manager::init();

	}

	/**
	 * Set Redirect flag on activation.
	 *
	 * @Hooked - register_activation_hook
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function activation_reset() {
		update_option( '__spectra_pro_do_redirect', true );
	}

	/**
	 * Check spectra core is installed or not.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_spectra_core_installed() {
		$path    = 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';
		$plugins = get_plugins();

		return isset( $plugins[ $path ] );
	}

	/**
	 * Admon Notice Callback if failed to load core.
	 *
	 * Hooked - admin_notices
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function spectra_pro_fail_load() {
		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		$plugin = 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';

		if ( $this->is_spectra_core_installed() ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );

			$message  = '<h3>' . esc_html__( 'Activate the Spectra Plugin', 'spectra-pro' ) . '</h3>';
			$message .= '<p>' . esc_html__( 'Before you can use all the features of Spectra Pro, you need to activate the Spectra plugin first.', 'spectra-pro' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate Now', 'spectra-pro' ) ) . '</p>';
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=ultimate-addons-for-gutenberg' ), 'install-plugin_ultimate-addons-for-gutenberg' );

			$message  = '<h3>' . esc_html__( 'Install and Activate the Spectra Plugin', 'spectra-pro' ) . '</h3>';
			$message .= '<p>' . esc_html__( 'Before you can use all the features of Spectra Pro, you need to install and activate the Spectra plugin first.', 'spectra-pro' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Spectra', 'spectra-pro' ) ) . '</p>';
		}//end if

		// Phpcs ignore comment is required as $message variable is already escaped.
		echo '<div class="error">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Admon Notice Callback if failed to load updated core.
	 *
	 * Hooked - admin_notices
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function spectra_pro_fail_load_out_of_date() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$file_path = 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';

		$upgrade_link = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file_path, 'upgrade-plugin_' . $file_path );
		$message      = '<p>' . esc_html__( 'Spectra Pro is not working because you are using an old version of Spectra.', 'spectra-pro' ) . '</p>';
		$message     .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $upgrade_link, esc_html__( 'Update Spectra Now', 'spectra-pro' ) ) . '</p>';

		// Phpcs ignore comment is required as $message variable is already escaped.
		echo '<div class="error">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Kicking this off by calling 'get_instance()' method
 */
PluginLoader::get_instance();
