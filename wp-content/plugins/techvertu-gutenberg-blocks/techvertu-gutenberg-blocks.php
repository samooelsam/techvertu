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
		__DIR__ . '/build/solution',
		array(
			'render_callback' => 'techvertu_meta_fields_metadata_block_render_callback',
		)
	);
	register_block_type(
		__DIR__ . '/build/related-posts',
		array(
			'render_callback' => 'techvertu_related_posts_block_render_callback',
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
function techvertu_related_posts_block_render_callback( $attributes, $content, $block ) { ?>
		<?php  $postCategoryArr = wp_get_post_categories(get_the_ID());
		if(is_singular()){
			$args = array(
				'post_type' => 'post',
				'posts_per_page' => 10,
				'category__in' => $postCategoryArr, // Array
				'post__not_in' => array(get_the_ID()),
				'orderby'=> 'date',
				'order'=>'DESC'
			);
			$widgetTitle = __('Related Posts', 'techvertu');
		}
		else{
			?>
			<script>
				jQuery(document).ready(function(){
					jQuery('.remove-on-blog-page').parent().remove();
				});
			</script>
			<?php 
		} ?>
		<div class="wp-block-group is-layout-flow wp-block-group-is-layout-flow remove-on-blog-page">
			<p class="has-medium-font-size"><strong><?php echo($widgetTitle);?></strong></p>
			<ul class="wp-block-latest-posts__list wp-block-latest-posts">
				<?php 
				$widgetQuery = new WP_Query( $args );
					while( $widgetQuery->have_posts() ):
						$widgetQuery->the_post();
						?> <li>
								<div class="wp-block-latest-posts__featured-image"><?php the_post_thumbnail('thumbnail');?></div>
								<a href="<?php the_permalink();?>"><?php the_title();?></a>
							</li>
					<?php endwhile;
					wp_reset_query();
					?>
			</ul>
		</div>
	<?php 
}