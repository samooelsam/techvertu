<?php 


add_image_size( 'single-post-size', 1034, 350, true );
// Remove the default comments from Astra.
add_action(
	'init',
	function() {
		if ( class_exists( 'Astra_Loop' ) ) {
			remove_action( 'astra_template_parts_content', array( Astra_Loop::get_instance(), 'template_parts_comments' ), 15 );
		}
	},
	11
);

add_filter('comment_form_field_url', '__return_false');

