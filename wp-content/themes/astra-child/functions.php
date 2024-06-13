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
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.1.8' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );
	wp_enqueue_style( 'astra-child-theme-custom-css', get_stylesheet_directory_uri() . '/assets/css/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );
	wp_enqueue_style( 'astra-child-theme-web-font','https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );
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
//**********************************************************************************
add_action( 'wp_enqueue_scripts', 'techvertu_google_recaptcha' );

function techvertu_google_recaptcha() {
    // Check if the current page is a single post
    if (is_single()) {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=6Lf1CmMpAAAAAJJSNY4nllZucYYqA31pbKqymTO7');
    }
}

/** 
 * Google reCAPTCHA: Add widget before the submit button 
 */ 
function add_google_recaptcha($submit_field) { 
    // Check if the current page is a single post
    if (is_single()) {
        $submit_field['submit_field'] = '<p class="submit"> 
            <input type="submit" name="buttonSubmit" id="buttonSubmit" class="submit" value="Post Comment"> 
            <input type="hidden" name="comment_post_ID" value="'.get_the_id().'" id="comment_post_ID"> 
            <input type="hidden" name="comment_parent" id="comment_parent" value="0"> 
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response"> 
        </p> 
        <script> 
        document.getElementById("buttonSubmit").onclick = function onClick(e) { 
            e.preventDefault(); 
            grecaptcha.ready(function() { 
                grecaptcha.execute("6Lf1CmMpAAAAAJJSNY4nllZucYYqA31pbKqymTO7", {action: "submit"}).then(function(token) { 
                    document.getElementById("g-recaptcha-response").value = token; 
                    document.getElementById("commentform").submit(); 
                }); 
            }); 
        } 
        </script> 
        '; 
    }
    return $submit_field; 
} 

if (!is_user_logged_in()) { 
    add_filter('comment_form_defaults','add_google_recaptcha'); 
} 
  
/** 
 * Google reCAPTCHA: verify response and validate comment submission 
 */ 
function is_valid_captcha_response($captcha) { 
    $captcha_postdata = http_build_query( 
        array( 
            'secret' => '6Lf1CmMpAAAAAEOaAp2-X23LGBB_r_fL6FbACmwE', 
            'response' => $captcha, 
            'remoteip' => $_SERVER['REMOTE_ADDR'] 
        ) 
    ); 
    $captcha_opts = array( 
        'http' => array( 
            'method' => 'POST', 
            'header' => 'Content-type: application/x-www-form-urlencoded', 
            'content' => $captcha_postdata 
        ) 
    ); 
    $captcha_context = stream_context_create($captcha_opts); 
    $captcha_response = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, $captcha_context), true); 
    if($captcha_response['success'] && $captcha_response['score'] > 0.5){ 
        return true; 
    } else { 
        return false; 
    } 
} 
 
function verify_google_recaptcha() { 
    $recaptcha = $_POST['g-recaptcha-response']; 
    if(empty($recaptcha)){ 
        wp_die(__("<p><strong>Error:</strong> Sorry, spam detected!</p><p><a href='javascript:history.back()'>« Back</a></p>")); 
    }elseif(!is_valid_captcha_response($recaptcha)){ 
        wp_die(__("<b>Sorry, spam detected!</b>")); 
    } 
} 
