<?php
namespace SpectraPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SpectraPro\Includes\Extensions\PopupBuilder\Spectra_Pro_Popup_Builder;

/**
 * Assets
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Assets {

	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();
		add_action( 'enqueue_block_editor_assets', array( $self, 'block_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $self, 'block_assets' ) );
		add_action( 'spectra_localize_pro_block_ajax', array( $self, 'localize_pro_block_ajax' ) );

		if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
			add_filter( 'block_categories_all', array( $self, 'register_block_category' ), 999999, 2 );
		} else {
			add_filter( 'block_categories', array( $self, 'register_block_category' ), 999999, 2 );
		}
	}

	/**
	 * Gutenberg block category for Spectra Pro.
	 *
	 * @param array  $categories Block categories.
	 * @param object $post Post object.
	 * @since 1.0.0
	 */
	public function register_block_category( $categories, $post ) {
		return array_merge(
			array(
				array(
					'slug'  => 'spectra-pro',
					'title' => __( 'Spectra Pro', 'spectra-pro' ),
				),
			),
			$categories
		);
	}

	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * @Hooked - enqueue_block_editor_assets
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function block_editor_assets() {
		wp_enqueue_style(
			'spectra-pro-block-css', // Handle.
			SPECTRA_PRO_URL . 'dist/style-blocks.css', // Block style CSS.
			array(),
			SPECTRA_PRO_VER
		);

		$script_dep_path = SPECTRA_PRO_DIR . 'dist/blocks.asset.php';
		$script_info     = file_exists( $script_dep_path )
			? include $script_dep_path
			: array(
				'dependencies' => array(),
				'version'      => SPECTRA_PRO_VER,
			);
		$script_dep      = array_merge( $script_info['dependencies'], array( 'wp-blocks', 'wp-i18n', 'uagb-block-editor-js' ) );

		// Scripts.
		wp_enqueue_script(
			'spectra-pro-block-editor-js', // Handle.
			SPECTRA_PRO_URL . 'dist/blocks.js',
			$script_dep, // Dependencies, defined above.
			$script_info['version'], // UAGB_VER.
			true // Enqueue the script in the footer.
		);

		wp_localize_script(
			'spectra-pro-block-editor-js',
			'spectra_pro_blocks_info',
			array(
				'category'                 => 'spectra-pro',
				'spectra_pro_url'          => SPECTRA_PRO_URL,
				'ajax_url'                 => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'               => wp_create_nonce( 'spectra_pro_ajax_nonce' ),
				'current_post_id'          => get_the_ID(),
				'is_allow_registration'    => (bool) get_option( 'users_can_register' ),
				'login_url'                => esc_url( wp_login_url( home_url() ) ),
				'admin_block_settings'     => admin_url( 'admin.php?page=spectra&path=settings&settings=block-settings' ),
				'anyone_can_register'      => admin_url( 'options-general.php#users_can_register' ),
				'enableDynamicContent'     => apply_filters( 'enable_dynamic_content', \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_dynamic_content', 'enabled' ) ),
				'uag_enable_gbs_extension' => \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_gbs_extension', 'enabled' ),
				'dynamic_content_mode'     => \UAGB_Admin_Helper::get_admin_settings_option( 'uag_dynamic_content_mode', 'popup' ),
				'display_rules'            => Spectra_Pro_Popup_Builder::get_location_selections(),
			)
		);
	}

	/**
	 * Enqueue Gutenberg block assets for frontend.
	 *
	 * @Hooked - enqueue_block_assets
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function block_assets() {
		wp_enqueue_style(
			'spectra-pro-block-css', // Handle.
			SPECTRA_PRO_URL . 'dist/style-blocks.css', // Block style CSS.
			array(),
			SPECTRA_PRO_VER
		);

	}

	/**
	 * Extend Core Front-end Dynamic Block Asset Localization.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function localize_pro_block_ajax() {

		// AJAX for Instagram Feed Block.
		$spectra_pro_instagram_masonry_ajax_nonce         = wp_create_nonce( 'spectra_pro_instagram_masonry_ajax_nonce' );
		$spectra_pro_instagram_grid_pagination_ajax_nonce = wp_create_nonce( 'spectra_pro_instagram_grid_pagination_ajax_nonce' );
		wp_localize_script(
			'uagb-instagram-feed-js',
			'spectra_pro_instagram_media',
			array(
				'ajax_url'                                 => admin_url( 'admin-ajax.php' ),
				'spectra_pro_instagram_masonry_ajax_nonce' => $spectra_pro_instagram_masonry_ajax_nonce,
				'spectra_pro_instagram_grid_pagination_ajax_nonce' => $spectra_pro_instagram_grid_pagination_ajax_nonce,
			)
		);
	}
}
