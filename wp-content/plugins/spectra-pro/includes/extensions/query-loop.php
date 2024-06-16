<?php
namespace SpectraPro\Includes\Extensions;

use SpectraPro\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * QueryLoop
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class QueryLoop {

	/**
	 * Initialization
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'register_block_loop_builder' ] );
		add_action( 'wp_enqueue_scripts', [ $self, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue Scripts.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'uagb-loop-builder', SPECTRA_PRO_URL . 'assets/js/loop-builder.js', array(), SPECTRA_PRO_VER, true );

	}

	/**
	 * Register loop builder blocks
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register_block_loop_builder() {
		$self = new self();

		register_block_type(
			'uagb/loop-builder',
			array(
				'provides_context' => array(
					'queryId'       => 'queryId',
					'query'         => 'query',
					'displayLayout' => 'displayLayout',
				),
			)
		);

		register_block_type(
			'uagb/loop-wrapper',
			array(
				'title'             => __( 'Wrapper', 'spectra-pro' ),
				'render_callback'   => [ $self, 'render_loop_wrapper' ],
				'uses_context'      => array(
					'queryId',
					'query',
					'displayLayout',
				),
				'skip_inner_blocks' => true,
			)
		);
	}

	/**
	 * Callback function for wrapper block
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content wrapper block content.
	 * @param \WP_Block $block wrapper block object.
	 * @return string
	 * @since 1.0.0
	 */
	public function render_loop_wrapper( $attributes, $content, $block ) {
		$page_key = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
		// callback function for wrapper block nonce verification not required.
		$page = empty( $_GET[ $page_key ] ) ? 1 : intval( $_GET[ $page_key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Use global query if needed.
		$use_global_query = ( isset( $block->context['query']['inherit'] ) && $block->context['query']['inherit'] );
		if ( $use_global_query ) {
			global $wp_query;
			$query = clone $wp_query;
		} else {
			$query_args = build_query_vars_from_query_block( $block, $page );
			$query_args = array_merge( $query_args, Utils::customize_block_query( $block ) );
			$query      = new \WP_Query( $query_args );
		}

		$content = '';
		while ( $query->have_posts() ) {
			$query->the_post();
			// Get an instance of the current Post Template block.
			$block_instance = $block->parsed_block;

			$block_content = (
			new \WP_Block(
				$block_instance,
				array(
					'postType' => get_post_type(),
					'postId'   => get_the_ID(),
				)
			)
			)->render( array( 'dynamic' => false ) );

			$content .= '<div class="uagb-loop-post"><div class="uagb-loop-post-inner">' . $block_content . '</div></div>';
		}//end while

		/*
		* Use this function to restore the context of the template tags
		* from a secondary query loop back to the main query loop.
		* Since we use two custom loops, it's safest to always restore.
		*/
		wp_reset_postdata();
		$query_id = is_int( $block->context['queryId'] ) ? $block->context['queryId'] : (int) $block->context['queryId'];
		return '<div id="uagb-block-queryid-' . $query_id . '" class="uagb-loop-container uagb-block-' . esc_attr( $attributes['block_id'] ) . '">' . $content . '</div>';
	}
}
