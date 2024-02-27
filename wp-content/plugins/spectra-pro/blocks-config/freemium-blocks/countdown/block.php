<?php
/**
 * Countdown Pro Block config file.
 */
namespace SpectraPro\BlocksConfig\FreemiumBlocks\Countdown;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Countdown.
 *
 * @since 1.0.0
 */
class Block {

	/**
	 * Static variable to keep track of processed blocks (as the function runs twice).
	 *
	 * @var array
	 * @since 1.0.0
	 */
	public static $processed_blocks = array();

	/**
	 * Micro Constructor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		$self = new self();
		// Priority less than 11 gives conflicts with Woocommerce Blocks' render_block filter, leading to fatal error.
		if ( ! is_admin() ) {
			add_filter( 'render_block', array( $self, 'countdown_render_block' ), 11, 2 );
		}
		add_filter( 'uagb_countdown_options', [ $self, 'add_countdown_options' ], 10, 3 );
		add_filter( 'spectra_countdown_attributes', [ $self, 'add_attributes_defaults' ] );
	}

	/**
	 * Add additional countdown options to pass in JS.
	 *
	 * @param array  $data data to filter.
	 * @param string $id Block ID.
	 * @param array  $atts Block Attributes.
	 * @return array $args JS arguments.
	 * @since 1.0.0
	 */
	public function add_countdown_options( $data, $id, $atts ) {
		$data['timerType']        = $atts['timerType'];
		$data['evergreenDays']    = $atts['evergreenDays'];
		$data['evergreenHrs']     = $atts['evergreenHrs'];
		$data['evergreenMinutes'] = $atts['evergreenMinutes'];
		$data['campaignID']       = $atts['campaignID'];
		$data['resetDays']        = $atts['resetDays'];

		return $data;
	}

	/**
	 * Add attributes to the countdown block.
	 *
	 * @param array $attributes The block attributes.
	 * @return array The block attributes.
	 * @since 1.0.0
	 */
	public function add_attributes_defaults( $attributes ) {
		return array_merge(
			$attributes,
			array(
				'evergreenDays'    => 0,
				'evergreenHrs'     => 0,
				'evergreenMinutes' => 0,
				'campaignID'       => '',
				'resetDays'        => 30,
			)
		);
	}


	/**
	 * Render block function for Countdown.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @since 1.0.0
	 * @return string|null|boolean Returns the new block content.
	 */
	public function countdown_render_block( $block_content, $block ) {

		// Check if it's NOT the countdown block and ensure the current page is the Gutenberg editor.
		// The second check ensures that HTTP redirects do not occur in the editor, else the user may never be able to edit the post once the countdown expires.
		// Third check: If the block attributes are empty, return the block content.
		if ( empty( $block['attrs'] ) || ( 'uagb/countdown' !== $block['blockName'] ) ) {
			return $block_content;
		}

		$block_attributes = $block['attrs'];

		// If the block has already been processed, return the block content.
		if ( in_array( $block['attrs']['block_id'], self::$processed_blocks, true ) ) {
			return $block_content;
		}

		// Add the current block to the list of processed blocks.
		array_push( self::$processed_blocks, $block['attrs']['block_id'] );

		$js_time      = strtotime( $block_attributes['endDateTime'] );
		$current_time = time();
		$timer_type   = isset( $block_attributes['timerType'] ) ? $block_attributes['timerType'] : 'date';

		if ( 'evergreen' === $timer_type ) {
			$campaign_id = ! empty( $block['attrs']['campaignID'] ) ? $block['attrs']['campaignID'] : $block['attrs']['block_id'];
			$site_slug   = sanitize_title( get_bloginfo( 'name' ) );
			$cookie_name = $site_slug . '-' . $campaign_id;

			if ( isset( $_COOKIE[ $cookie_name ] ) ) {
				// Converting PHP timestamp to JS timestamp.
				$current_time = $current_time * 1000;
				$diff         = absint( $_COOKIE[ $cookie_name ] ) - $current_time;
				$js_time      = $current_time + $diff;
			} else {
				$evergreen_days = isset( $block['attrs']['evergreenDays'] ) ? $block['attrs']['evergreenDays'] : 0;
				$evergreen_hrs  = isset( $block['attrs']['evergreenHrs'] ) ? $block['attrs']['evergreenHrs'] : 0;
				$evergreen_mins = isset( $block['attrs']['evergreenMinutes'] ) ? $block['attrs']['evergreenMinutes'] : 0;
				$js_time        = $current_time + ( ( $evergreen_days * 24 * 60 ) + ( $evergreen_hrs * 60 ) + $evergreen_mins ) * 60;
				$current_time   = $current_time + ( $evergreen_days * 24 * 60 * 60 ) + ( $evergreen_hrs * 60 * 60 ) + ( $evergreen_mins * 60 );
			}
		}

		$is_overtime = $current_time > $js_time;

		$timer_end_action = isset( $block_attributes['timerEndAction'] ) ? $block_attributes['timerEndAction'] : 'zero';

		// If countdown isn't overtime or the end action is set to stay at zero, show it.
		// Also in case it's set to replace with content, we need to process it further to remove the innerblocks in case it's not overtime.
		// Moreover, for 'redirect' case, we need further JS.
		if ( 'zero' === $timer_end_action ) {
			return $block_content;
		}

		if ( ! $is_overtime ) {

			if ( 'redirect' === $timer_end_action ) {
				$redirect_url = ! empty( $block_attributes['redirectURL']['url'] ) ? $block_attributes['redirectURL']['url'] : home_url( '/' );
				ob_start();
				// If global flag variable does not exists, create it.
				// This is used like a signal for usage in Pro code.
				// Later, simulate an HTTP redirect if the timer end signal is received.
				?>
				<script>
					if( ! window.UAGBCountdownTimeSignal ) {
						window.UAGBCountdownTimeSignal = {};
					}

					setInterval( () => {
						if ( window.UAGBCountdownTimeSignal[ <?php echo "'.uagb-block-" . esc_html( $block_attributes['block_id'] ) . "'"; ?> ] ) {
							window.location.replace( <?php echo "'" . esc_url( $redirect_url ) . "'"; ?> );
						}
					}, 1000 );
				</script>
				<?php
				$ob_check = ob_get_clean();
				return $ob_check ? $ob_check . $block_content : $block_content;
			} elseif ( 'hide' === $timer_end_action ) {
				ob_start();
				// If global flag variable does not exists, create it.
				// This is used like a signal for usage in Pro code.
				// If a positive overtime signal is received, delete the Countdown node. 
				?>
				<script>
					if( ! window.UAGBCountdownTimeSignal ) {
						window.UAGBCountdownTimeSignal = {};
					}

					setInterval( () => {
						if ( window.UAGBCountdownTimeSignal[ '.uagb-block-' + '<?php echo esc_attr( $block['attrs']['block_id'] ); ?>' ] ) {
							document.querySelector( '.uagb-block-' + '<?php echo esc_attr( $block['attrs']['block_id'] ); ?>' )?.remove();
						}
					}, 1000 );
				</script>
				<?php
				$ob_check = ob_get_clean();
				return $ob_check ? $ob_check . $block_content : $block_content;
			}//end if
		}//end if

		// If the timer is overtime AND end action is not 'keep the timer at zero'.
		if ( ( $is_overtime ) && ( 'zero' !== $timer_end_action ) ) {

			if ( 'hide' === $timer_end_action ) {
				return null;
			}

			if ( 'redirect' === $timer_end_action ) {
				$redirect_url = ! empty( $block_attributes['redirectURL']['url'] ) ? $block_attributes['redirectURL']['url'] : home_url( '/' );
				ob_start();
				// Simulate an HTTP redirect.
				?>
				<script>
					window.location.replace( <?php echo "'" . esc_url( $redirect_url ) . "'"; ?> );
				</script>
				<?php
				return ob_get_clean();
			}
		}//end if

		// If 'Replace with Content' is enabled.
		if ( ( 'content' === $timer_end_action ) ) {

			// If countdown is overtime.
			if ( $is_overtime ) {
				return $this->remove_time_boxes( $block_content, $block );
			}//end if

			// If countdown is NOT overtime, then remove the innerblocks.
			// Go through each innerblock, save it's html structure (in string format) and remove the same from block content.
			// Dynamic blocks aren't removed via this so we also use JS later.
			// But removing most info on server side minimizes the chance 'surprise data' being revealed before the countdown ends.
			$block_content = $this->blocks_remover( $block['innerBlocks'], $block_content );

			// Since dynamic blocks still remain, we remove the innerblocks via JS too.
			ob_start();
			?>
			<script>
				window.addEventListener( 'DOMContentLoaded', ( event ) => {
					const block_id = '<?php echo esc_attr( $block_attributes['block_id'] ); ?>';
					const innerblocks_wrapper_selector = '.uagb-block-countdown-innerblocks-' + block_id;
					const innerblocks_wrapper = document.querySelector( innerblocks_wrapper_selector );
					innerblocks_wrapper?.remove();
				} );
			</script>
			<?php
			$ob_check = ob_get_clean();
			return $ob_check ? $ob_check . $block_content : $block_content;

		}//end if

		return $block_content;
	}

	/**
	 * Recursively removes innerblocks.
	 * Limitations: Cannot remove dynamic blocks content.
	 *
	 * @param array  $blocks The innerblock content.
	 * @param string $block_content The block content.
	 * @since 1.0.0
	 * @return string Returns the new block content with the innerblocks removed.
	 */
	public function blocks_remover( $blocks, $block_content ) {

		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return $block_content;
		}

		foreach ( $blocks as $inner_block ) {
			// Remove the current instance of innerblock from block content.
			$block_content = str_replace( $inner_block['innerHTML'], '', $block_content );
	
			// Check if the inner block has inner blocks.
			if ( isset( $inner_block['innerBlocks'] ) && count( $inner_block['innerBlocks'] ) > 0 ) {
				// Recursively remove inner blocks within inner blocks.
				$block_content = $this->blocks_remover( $inner_block['innerBlocks'], $block_content );
			}
		}
	
		return $block_content;
	}

	/**
	 * Render block function for Countdown.
	 *
	 * @param mixed $block_content The block content.
	 * @param array $block The block data.
	 * @since 1.0.0
	 * @return string Returns the new block content with time boxes removed (days, hours, mins, seconds).
	 */
	public function remove_time_boxes( $block_content, $block ) {
		ob_start();
		// We need DOMContentLoaded to ensure all elements exist before performing the following operations.
		// Remove Countdown's time boxes - Days, Hours, Mins, Seconds Boxes.
		// Then show the innerblocks and unset the flex display for parent wrapper.
		?>
		<script>
			window.addEventListener('DOMContentLoaded', (event) => {

				const block_id = '<?php echo esc_attr( $block['attrs']['block_id'] ); ?>';

				const time_boxes_selector = '.uagb-block-' + block_id + ' .wp-block-uagb-countdown__box';
				const time_boxes_wrappers = document.querySelectorAll( time_boxes_selector );

				time_boxes_wrappers.forEach( ( time_box ) => {
					time_box.remove();
				} )

				const innerblocks_selector = '.uagb-block-countdown-innerblocks-' + block_id;
				const innerblocks_wrapper = document.querySelector( innerblocks_selector );

				innerblocks_wrapper.style.display = 'unset';
				innerblocks_wrapper.parentElement.style.display = 'unset';

			});
		</script>
		<?php

		return ob_get_clean() . $block_content;
	}

}
