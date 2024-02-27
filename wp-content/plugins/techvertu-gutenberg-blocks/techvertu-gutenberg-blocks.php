<?php
/**
 * Plugin Name:       Techvertu Gutenberg Blocks
 * Description:       Creating blocks for techvertu website.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Saman Tohidian
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       techvertu-gutenberg-blocks
 *
 * @package           create-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Register the custom meta fields
 */
function techvertu_meta_fields_register_meta() {

    $metafields = [ '_techvertu_solution_title', '_techvertu_solution_content' ];

    foreach( $metafields as $metafield ){
        // Pass an empty string to register the meta key across all existing post types.
        register_post_meta( '', $metafield, array(
            'show_in_rest' => true,
            'type' => 'string',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function() { 
                return current_user_can( 'edit_posts' );
            }
        ));
    }  
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => 'techvertu_meta_fields_metadata_block_render_callback',
		)
	);
}
add_action( 'init', 'techvertu_meta_fields_register_meta' );

function techvertu_meta_fields_metadata_block_render_callback( $attributes, $content, $block ) {
	
	$book_title = get_post_meta( get_the_ID(), '_techvertu_solution_title', true );
	$book_author = get_post_meta( get_the_ID(), '_techvertu_solution_content', true );
    
	$output = "";

	if( ! empty( $book_title ) ){
		$output .= '<h3>' . esc_html( $book_title ) . '</h3>';
	}
	if( ! empty( $book_author ) ){
		$output .= '<p>' . __( 'Book author: ' ) . esc_html( $book_author ) . '</p>';
	}
	if( strlen( $output ) > 0 ){
		return '<div ' . get_block_wrapper_attributes() . '>' . $output . '</div>';
	} else {
		return '<div ' . get_block_wrapper_attributes() . '>' . '<strong>' . __( 'Sorry. No fields available here!' ) . '</strong>' . '</div>';
	}
}