<?php 
/**
 * The sidebar containing the main widget area.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */
//  category: 22
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$astra_sidebar = apply_filters( 'astra_get_sidebar', 'sidebar-1' );

echo '<div ';
	echo astra_attr(
		'sidebar',
		array(
			'id'    => 'secondary',
			'class' => join( ' ', astra_get_secondary_class() ),
		)
	);
	echo '>';
	?>
    <div class="techvertu-sidebar-holder clearfix">
        <?php  if(is_single()) { 
            $solutionTitle = get_post_meta( get_the_ID(), '_techvertu_solution_title', true );
            $solutionContent = get_post_meta( get_the_ID(), '_techvertu_solution_content', true );
            $quickAccessClass = 'quick-access-margin';
            ($solutionContent && $quickAccessClass) ? $widgetTitle = "Quick Access on this page" : $widgetTitle = "Share this article";
            ?>
        <div class="techvertu-custom-widget quick-access">
            <h3><?php echo($widgetTitle);?></h3>
            <div class="techvertu-custom-widget-content clearfix">
                <?php if($solutionTitle && $solutionContent) {
                    $quickAccessClass = '';?>
                    <div class="quick-acs clearfix">
                        <ul>
                            <li>
                                <a href="#primary">
                                    <figure class="icon-holder">
                                        <i class="Problem-icon"></i>
                                    </figure>
                                    <?php _e('Problem', 'astra-child');?>
                                </a>
                            </li>
                            <li>
                                <a href="#solution">
                                    <figure class="icon-holder">
                                        <i class="solution-icon"></i>
                                    </figure>
                                    <?php _e('Solution', 'astra-child');?>
                                </a>
                            </li>
                            <li>
                                <a href="#comment">
                                    <figure class="icon-holder">
                                        <i class="comment-icon"></i>
                                    </figure>
                                    <?php _e('Comments', 'astra-child');?>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php }?>
                <div class="sharer clearfix <?php echo($quickAccessClass);?>">
                    <a href="" class="share-icon">
                        <i>
                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.722 13.4453C17.0289 13.4458 16.3464 13.615 15.7333 13.9383C15.1202 14.2615 14.595 14.729 14.2029 15.3006L8.23635 12.6065C8.66014 11.5832 8.66178 10.4337 8.24093 9.40917L14.1993 6.70133C14.7803 7.54169 15.6433 8.14597 16.6317 8.40454C17.6201 8.66312 18.6685 8.55887 19.5866 8.11071C20.5048 7.66255 21.2319 6.90016 21.636 5.96183C22.0402 5.0235 22.0947 3.97138 21.7896 2.99633C21.4845 2.02127 20.84 1.18786 19.9731 0.64726C19.1062 0.106664 18.0742 -0.105307 17.0644 0.0497963C16.0545 0.2049 15.1337 0.716804 14.469 1.49265C13.8042 2.2685 13.4396 3.25691 13.4412 4.27858C13.4451 4.5204 13.4696 4.76144 13.5145 4.99909L7.18035 7.87742C6.572 7.30745 5.8104 6.9276 4.98913 6.78453C4.16785 6.64146 3.32268 6.74141 2.55743 7.07209C1.79218 7.40277 1.14018 7.94978 0.681547 8.64592C0.222912 9.34206 -0.022389 10.157 -0.0242206 10.9906C-0.0260523 11.8243 0.215666 12.6403 0.671237 13.3385C1.12681 14.0366 1.77639 14.5865 2.54018 14.9205C3.30397 15.2545 4.1487 15.3582 4.9706 15.2187C5.79249 15.0793 6.55575 14.7028 7.1666 14.1355L13.5173 17.0028C13.4732 17.2403 13.4489 17.481 13.4449 17.7224C13.4447 18.5686 13.6954 19.3959 14.1655 20.0995C14.6355 20.8032 15.3036 21.3517 16.0854 21.6756C16.8671 21.9995 17.7274 22.0843 18.5573 21.9193C19.3873 21.7542 20.1496 21.3468 20.748 20.7484C21.3464 20.15 21.7538 19.3877 21.9189 18.5577C22.0839 17.7278 21.9991 16.8675 21.6752 16.0858C21.3513 15.304 20.8028 14.6359 20.0991 14.1659C19.3955 13.6958 18.5682 13.4451 17.722 13.4453V13.4453ZM17.722 1.83383C18.2056 1.83365 18.6783 1.97688 19.0805 2.2454C19.4827 2.51393 19.7961 2.89568 19.9813 3.34238C20.1665 3.78909 20.2151 4.28067 20.1208 4.75497C20.0266 5.22927 19.7938 5.66496 19.452 6.00696C19.1101 6.34896 18.6745 6.58189 18.2002 6.67629C17.726 6.7707 17.2344 6.72234 16.7876 6.53733C16.3408 6.35232 15.9589 6.03897 15.6903 5.63691C15.4216 5.23485 15.2782 4.76215 15.2782 4.27858C15.2787 3.6305 15.5363 3.00909 15.9944 2.55075C16.4526 2.0924 17.0739 1.83456 17.722 1.83383V1.83383ZM4.27818 13.4453C3.79462 13.4454 3.32186 13.3022 2.9197 13.0337C2.51755 12.7652 2.20405 12.3834 2.01888 11.9367C1.8337 11.49 1.78515 10.9984 1.87938 10.5241C1.97361 10.0498 2.20638 9.61412 2.54824 9.27213C2.89011 8.93013 3.32572 8.6972 3.79998 8.60279C4.27424 8.50839 4.76585 8.55675 5.21262 8.74176C5.65939 8.92677 6.04127 9.24012 6.30994 9.64218C6.57861 10.0442 6.72202 10.5169 6.72202 11.0005C6.72129 11.6485 6.46361 12.2698 6.00549 12.7281C5.54736 13.1864 4.92619 13.4443 4.27818 13.4453ZM17.722 20.1672C17.2385 20.1672 16.7658 20.0238 16.3638 19.7552C15.9618 19.4865 15.6484 19.1047 15.4634 18.658C15.2783 18.2113 15.2299 17.7197 15.3242 17.2455C15.4186 16.7712 15.6514 16.3356 15.9933 15.9937C16.3352 15.6518 16.7708 15.419 17.2451 15.3246C17.7193 15.2303 18.2109 15.2787 18.6576 15.4638C19.1043 15.6488 19.4861 15.9622 19.7548 16.3642C20.0234 16.7662 20.1668 17.2389 20.1668 17.7224C20.1663 18.3707 19.9086 18.9922 19.4502 19.4506C18.9918 19.909 18.3703 20.1667 17.722 20.1672V20.1672Z" fill="#76778B"/>
                            </svg>
                        </i>
                    </a>
                    <?php techvertu_social_share();?>
                </div>
            </div>
        </div>
        <?php 
    }?>
        <div class="sidebar-main" <?php /** @psalm-suppress TooManyArguments */ echo apply_filters( 'astra_sidebar_data_attrs', '', $astra_sidebar ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, Generic.Commenting.DocComment.MissingShort ?>>
            <?php astra_sidebars_before(); ?>

            <?php

            if ( is_active_sidebar( $astra_sidebar ) ) :
                    dynamic_sidebar( $astra_sidebar );
            endif;

            astra_sidebars_after();
            ?>

        </div><!-- .sidebar-main -->
    </div>
</div><!-- #secondary -->