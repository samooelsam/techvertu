<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.1.2' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );
	wp_enqueue_style( 'astra-child-theme-custom-css', get_stylesheet_directory_uri() . '/assets/css/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );
	wp_enqueue_style( 'astra-child-theme-web-font','http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );
	wp_enqueue_script('astra-child-scrollbar-scripts', get_template_directory_uri() . '-child/assets/js/jquery.scrollbar.min.js', array(), CHILD_THEME_ASTRA_CHILD_VERSION, true);
	wp_enqueue_script('astra-child-scripts', get_template_directory_uri() . '-child/assets/js/scripts.js', array(), CHILD_THEME_ASTRA_CHILD_VERSION, true);

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

get_template_part('includes/astrachild-config', 'config');

get_template_part('includes/socialSharer', 'config');


function techvertu_comment( $comment, $args, $depth ) {

	if ( 'div' === $args['style'] ) {
		$tag       = 'div';
		$add_below = 'comment';
	}
	else {
		$tag       = 'li';
		$add_below = 'div-comment';
	}

	$classes = ' ' . comment_class( empty( $args['has_children'] ) ? '' : 'parent', null, null, false );
	?>

	<<?= $tag . $classes; ?> id="comment-<?php comment_ID() ?>">
	<?php if ( 'div' != $args['style'] ) { ?>
		<div id="div-comment-<?php comment_ID() ?>" class="comment-body">
		<?php
	} ?>

	<div class="comment-author vcard">
		<?php
		if ( $args['avatar_size'] != 0 ) {
			echo get_avatar( $comment, $args['avatar_size'] );
		}
		printf(
			__( '<cite class="fn">%s</cite> <span class="says">says:</span>' ),
			get_comment_author_link()
		);
		?>
		<span class="reply">
			<?php
			comment_reply_link(
				array_merge(
					$args,
					array(
						'add_below' => $add_below,
						'depth'     => $depth,
						'max_depth' => $args['max_depth']
					)
				)
			); ?>
		</span>
	</div>

	<?php if ( $comment->comment_approved == '0' ) { ?>
		<em class="comment-awaiting-moderation">
			<?php _e( 'Your comment is awaiting moderation.' ); ?>
		</em><br/>
	<?php } ?>

	

	<?php comment_text(); ?>

	

	<?php if ( 'div' != $args['style'] ) { ?>
		</div>
	<?php }
}
add_action('template_redirect', 'techvertu_referrer_check');
function techvertu_referrer_check () 
{
	$referer = $_SERVER['HTTP_REFERER'];
	if($referer && str_contains($referer ,'https://www.techvertu.co.uk') == false){
		$fp = fopen('file.txt', 'a');
		fwrite($fp, $referer. "\n"); 
		fclose($fp);
	}
}
