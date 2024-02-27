<?php
/**
 * The template for displaying all single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>
<?php if ( astra_page_layout() == 'left-sidebar' ) : ?>

	<?php get_sidebar(); ?>

<?php endif ?>

	<div id="primary" <?php astra_primary_class(); ?>>
				
		<?php 
		astra_primary_content_top(); 
		if(has_post_thumbnail()) {?>
		<figure class="techvertu-post-image-wrapper clearfix">
			<?php 
				the_post_thumbnail('single-post-size');
			?>
		</figure>
		<?php }
		astra_content_loop(); ?>
		
		<?php astra_primary_content_bottom(); ?>

	</div><!-- #primary -->

<?php if ( astra_page_layout() == 'right-sidebar' ) : ?>

	<?php get_sidebar(); ?>

<?php endif; ?>
</div>
<?php 
$solutionTitle = get_post_meta( get_the_ID(), '_techvertu_solution_title', true );
$solutionContent = get_post_meta( get_the_ID(), '_techvertu_solution_content', true );
if($solutionTitle || $solutionContent) {
?>
	<div class="section-solution clearfix" id="solution">
		<div class="centerize grdi_12 clearfix">
			<div class="left-solution clearfix">
				<h3>
					<svg width="70" height="36" viewBox="0 0 70 36" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M48.7402 1.36269C48.1935 0.816171 47.4521 0.509155 46.6791 0.509155C45.9061 0.509155 45.1648 0.816171 44.6181 1.36269L17.5271 28.4537L5.01208 15.9387C4.74316 15.6603 4.42148 15.4382 4.06582 15.2854C3.71015 15.1326 3.32762 15.0522 2.94054 15.0488C2.55346 15.0455 2.16959 15.1192 1.81132 15.2658C1.45305 15.4124 1.12756 15.6289 0.853844 15.9026C0.580127 16.1763 0.363664 16.5018 0.217085 16.86C0.0705055 17.2183 -0.00325354 17.6022 0.00011007 17.9893C0.00347368 18.3763 0.0838934 18.7589 0.236676 19.1145C0.38946 19.4702 0.611547 19.7919 0.889979 20.0608L15.466 34.6368C16.0127 35.1833 16.7541 35.4903 17.5271 35.4903C18.3001 35.4903 19.0414 35.1833 19.5881 34.6368L48.7402 5.48478C49.2867 4.9381 49.5937 4.19674 49.5937 3.42374C49.5937 2.65073 49.2867 1.90937 48.7402 1.36269Z" fill="#70BB25"/>
						<path d="M69.1465 1.36269C68.5998 0.816171 67.8585 0.509155 67.0854 0.509155C66.3124 0.509155 65.5711 0.816171 65.0244 1.36269L37.9334 28.4537L30.3953 20.9156C27.9068 22.5492 26.9378 23.1854 25.4184 24.1829L35.8723 34.6368C36.419 35.1833 37.1604 35.4903 37.9334 35.4903C38.7064 35.4903 39.4477 35.1833 39.9944 34.6368L69.1465 5.48478C69.693 4.9381 70 4.19674 70 3.42374C70 2.65073 69.693 1.90937 69.1465 1.36269Z" fill="#70BB25"/>
					</svg>
					<?php _e('Solution', 'astra-child');?>
				</h3>
				<p><?php print_r($solutionTitle);?></p>
				<p><?php print_r($solutionContent);?></p>
				<?php techvertu_social_share();?>
			</div>
			<div class="right-solution clearfix">
				<img src="<?php echo(bloginfo('template_directory'));?>-child/assets/img/solution.svg" />
			</div>
		</div>
	</div>
<?php }?>
<div class="comment-form-wrapper clearfix" id="comment">
	<div class="centerize clearfix">
		<?php (is_user_logged_in()) ? $loggedIn = 'logged-in' : $loggedIn = '';?>
		<div class="comment-column clearfix <?php echo($loggedIn);?>">
			<header class="comment-header clearfix">
				<figure class="image-wrapper clearfix">

					<svg width="76" height="76" viewBox="0 0 76 76" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_13_398)">
							<path d="M45.916 22.1665C42.9659 22.1207 40.1183 23.2483 37.9994 25.3015C36.3703 23.7803 34.3276 22.7751 32.1284 22.4123C29.9293 22.0496 27.6719 22.3456 25.6406 23.2631C23.6093 24.1805 21.8949 25.6785 20.7131 27.5683C19.5313 29.4581 18.935 31.6553 18.9994 33.8832C18.9994 44.1939 34.4337 55.2139 36.1944 56.433C36.7245 56.8008 37.3542 56.9978 37.9994 56.9978C38.6445 56.9978 39.2743 56.8008 39.8044 56.433C41.565 55.2139 56.9994 44.1939 56.9994 33.8832C57.0778 30.8613 55.9554 27.9315 53.878 25.7354C51.8007 23.5393 48.9376 22.256 45.916 22.1665ZM37.9994 49.9129C32.4862 45.7012 25.3327 38.6395 25.3327 33.8832C25.092 27.5309 34.5572 26.2832 34.8327 33.1042V34.8332C34.8327 35.6731 35.1663 36.4785 35.7602 37.0724C36.3541 37.6662 37.1595 37.9999 37.9994 37.9999C38.8392 37.9999 39.6447 37.6662 40.2385 37.0724C40.8324 36.4785 41.166 35.6731 41.166 34.8332V33.1042C41.4415 26.2895 50.9067 27.5309 50.666 33.8832C50.666 38.6332 43.5125 45.7012 37.9994 49.9129ZM40.6467 0.0885384C35.2182 -0.290803 29.7715 0.50021 24.6751 2.40806C19.5788 4.31592 14.9516 7.29611 11.1067 11.147C7.26183 14.9979 4.28886 19.6297 2.38896 24.729C0.489066 29.8284 -0.293446 35.2763 0.0943664 40.7042C1.4877 60.8252 19.259 75.9999 41.4289 75.9999H60.166C64.3638 75.9949 68.3881 74.3251 71.3563 71.3569C74.3246 68.3886 75.9943 64.3643 75.9994 60.1666V39.0765C76.1167 29.3153 72.5375 19.871 65.9802 12.6394C59.4229 5.40773 50.3728 0.924112 40.6467 0.0885384V0.0885384ZM69.666 60.1666C69.666 62.6861 68.6651 65.1025 66.8836 66.8841C65.102 68.6657 62.6856 69.6666 60.166 69.6666H41.4289C22.3212 69.6666 7.59937 57.3165 6.41187 40.2672C6.08677 35.7421 6.7378 31.1999 8.32089 26.9482C9.90397 22.6966 12.3821 18.8347 15.5876 15.6241C18.7931 12.4136 22.651 9.92927 26.9001 8.33942C31.1492 6.74957 35.6904 6.09131 40.216 6.40921C48.3432 7.12815 55.8971 10.8961 61.3601 16.956C66.8232 23.0158 69.7906 30.9186 69.666 39.0765V60.1666Z" fill="#0B406A"/>
						</g>
						<defs>
							<clipPath id="clip0_13_398">
								<rect width="76" height="76" fill="white"/>
							</clipPath>
						</defs>
					</svg>
				</figure>
				<div class="header-texts clearfix">
					<span><?php _e('anything else?', 'astra-child');?></span>
					<h3><?php _e('Lets Talk!', 'astra-child');?></h3>
					<p><?php _e('If you have additional comments or questions about this article, you can share them in this section.', 'astra-child');?></p>
				</div>
			</header>
			<div>
			<?php 
			$comments_args = array(
					'label_submit' => __( 'Submit', 'textdomain' ),
					'title_reply' => __( '', 'textdomain' ),
					'comment_notes_after' => '',
					'comment_field' => '<p class="comment-form-comment"><br /><textarea id="comment" name="comment" aria-required="true" placeholder="Write your comment here . . ."></textarea></p>',
			);
			comment_form( $comments_args );
			?>
			</div>
		</div>
		<?php if(!is_user_logged_in()) {?>
		</div>
		<?php } 
		$comments = get_comments(array('status' => 'approve', 'post_id' => get_the_ID(), 'order' => 'DESC'));
		if($comments) {?>
		<div class="comment-column clearfix ">
			<div class="techvertu-comment-holder scrollbar-outer clearfix">
			<?php
				wp_list_comments(array(
					'avatar_size' => 70,
					'reverse_top_level' => false, //Show the oldest comments at the top of the list
					'avatar_size'       => 0,
					'format'            => 'html5',
					'style' => 'div',
					'callback' => 'techvertu_comment'
				), $comments);
				?>
			</div>
		</div>
		<?php }?>
	</div>
</div>

<?php get_footer(); ?>
