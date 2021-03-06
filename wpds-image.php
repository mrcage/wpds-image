<?php
/*
Plugin Name: WPDS Image Widget
Plugin URI:
Description: A simple image widget that uses the native WordPress media manager to add image widgets to your site.
Author: Nicolas Perrenoud
Version: 1.0
Author URI:
Text Domain: wpds-image
*/

// Block direct requests
if ( !defined('ABSPATH') ) {
	die('-1');
}

// Load the widget on widgets_init
function tribe_load_image_widget() {
	register_widget('Tribe_Image_Widget');
}
add_action('widgets_init', 'tribe_load_image_widget');

/**
 * Tribe_Image_Widget class
 **/
class Tribe_Image_Widget extends WP_Widget {

	const VERSION = '4.2.2';

	const CUSTOM_IMAGE_SIZE_SLUG = 'tribe_image_widget_custom';

	/**
	 * Widget constructor
	 */
	public function __construct() {
		load_plugin_textdomain( 'wpds-image', false, trailingslashit(basename(dirname(__FILE__))) . 'languages/');
		$widget_ops = array( 'classname' => 'widget_sp_image', 'description' => __( 'Showcase a single image.', 'wpds-image' ) );
		$control_ops = array( 'id_base' => 'widget_sp_image' );
		parent::__construct('widget_sp_image', __('Image Widget', 'wpds-image'), $widget_ops, $control_ops);

		add_action( 'sidebar_admin_setup', array( $this, 'admin_setup' ) );
		add_action( 'admin_head-widgets.php', array( $this, 'admin_head' ) );

	}

	/**
	 * Test to see if this version of WordPress supports the new image manager.
	 * @return bool true if the current version of WordPress does NOT support the current image management tech.
	 */
	private function use_old_uploader() {
		if ( defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) ) return true;
		return !function_exists('wp_enqueue_media');
	}

	/**
	 * Enqueue all the javascript.
	 */
	public function admin_setup() {
		wp_enqueue_media();
		wp_enqueue_script( 'tribe-image-widget', plugins_url('resources/js/image-widget.js', __FILE__), array( 'jquery', 'media-upload', 'media-views' ), self::VERSION );

		wp_localize_script( 'tribe-image-widget', 'TribeImageWidget', array(
			'frame_title' => __( 'Select an Image', 'wpds-image' ),
			'button_title' => __( 'Insert Into Widget', 'wpds-image' ),
		) );
	}

	/**
	 * Widget frontend output
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$instance = wp_parse_args( (array) $instance, self::get_defaults() );
		if ( !empty( $instance['imageurl'] ) || !empty( $instance['attachment_id'] ) ) {

			$instance['padding'] = apply_filters( 'image_widget_image_maxwidth', esc_attr( $instance['padding'] ), $args, $instance );

			if ( !defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) ) {
				$instance['attachment_id'] = ( $instance['attachment_id'] > 0 ) ? $instance['attachment_id'] : $instance['image'];
				$instance['attachment_id'] = apply_filters( 'image_widget_image_attachment_id', abs( $instance['attachment_id'] ), $args, $instance );
				$instance['size'] = apply_filters( 'image_widget_image_size', esc_attr( $instance['size'] ), $args, $instance );
			}
			$instance['imageurl'] = apply_filters( 'image_widget_image_url', esc_url( $instance['imageurl'] ), $args, $instance );

			// No longer using extracted vars. This is here for backwards compatibility.
			extract( $instance );

			include( $this->getTemplateHierarchy( 'widget' ) );
		}
	}

	/**
	 * Update widget options
	 *
	 * @param object $new_instance Widget Instance
	 * @param object $old_instance Widget Instance
	 * @return object
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, self::get_defaults() );
		$instance['padding'] = $new_instance['padding'];
		if ( !defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) ) {
			$instance['size'] = $new_instance['size'];
		}

		// Reverse compatibility with $image, now called $attachement_id
		if ( !defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) && $new_instance['attachment_id'] > 0 ) {
			$instance['attachment_id'] = abs( $new_instance['attachment_id'] );
		} elseif ( $new_instance['image'] > 0 ) {
			$instance['attachment_id'] = $instance['image'] = abs( $new_instance['image'] );
		}
		$instance['imageurl'] = $new_instance['imageurl']; // deprecated

		$instance['aspect_ratio'] = $this->get_image_aspect_ratio( $instance );

		return $instance;
	}

	/**
	 * Form UI
	 *
	 * @param object $instance Widget Instance
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, self::get_defaults() );
		include( $this->getTemplateHierarchy( 'widget-admin' ) );
	}

	/**
	 * Admin header css
	 */
	public function admin_head() {
		?>
	<style type="text/css">
		.uploader input.button {
			width: 100%;
			height: 34px;
			line-height: 33px;
			margin-top: 15px;
		}
		.tribe_preview .aligncenter {
			display: block;
			margin-left: auto !important;
			margin-right: auto !important;
		}
		.tribe_preview {
			overflow: hidden;
			max-height: 300px;
		}
		.tribe_preview img {
			margin: 10px 0;
			height: auto;
		}
	</style>
	<?php
	}

	/**
	 * Render an array of default values.
	 *
	 * @return array default values
	 */
	private static function get_defaults() {

		$defaults = array(
			'image' => 0, // reverse compatible - now attachement_id
			'imageurl' => '', // reverse compatible.
			'padding' => 10,
		);

		if ( !defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) ) {
			$defaults['size'] = self::CUSTOM_IMAGE_SIZE_SLUG;
			$defaults['attachment_id'] = 0;
		}

		return $defaults;
	}

	/**
	 * Render the image html output.
	 *
	 * @param array $instance
	 * @param bool $include_link will only render the link if this is set to true. Otherwise link is ignored.
	 * @return string image html
	 */
	private function get_image_html( $instance, $include_link = true ) {

		// Backwards compatible image display.
		if ( $instance['attachment_id'] == 0 && $instance['image'] > 0 ) {
			$instance['attachment_id'] = $instance['image'];
		}

		$output = '';

		if ( !empty( $instance['attachment_id'] ) ) {
			$image_details = wp_get_attachment_image_src( $instance['attachment_id'], 'full' );
			if ($image_details) {
				$instance['imageurl'] = $image_details[0];
			}
		}

		$attr = array();
		$attr['alt'] = isset($instance['imageurl']) ? basename($instance['imageurl']) : $instance['attachment_id'];
		$attr['style'] = 'max-width: 100%; max-height: 100%;';
		$attr = apply_filters( 'image_widget_image_attributes', $attr, $instance );

		// If there is an imageurl, use it to render the image. Eventually we should kill this and simply rely on attachment_ids.
		if ( !empty( $instance['imageurl'] ) ) {
			// If all we have is an image src url we can still render an image.
			$attr['src'] = $instance['imageurl'];
			$attr = array_map( 'esc_attr', $attr );
			$output .= rtrim("<img");
			foreach ( $attr as $name => $value ) {
				$output .= sprintf( ' %s="%s"', $name, $value );
			}
			$output .= ' />';
		} elseif( abs( $instance['attachment_id'] ) > 0 ) {
			$output .= wp_get_attachment_image($instance['attachment_id'], 'full', false, $attr);
		}

		return $output;
	}

	/**
	 * Establish the aspect ratio of the image.
	 *
	 * @param $instance
	 * @return float|number
	 */
	private function get_image_aspect_ratio( $instance ) {
		if ( !empty( $instance['aspect_ratio'] ) ) {
			return abs( $instance['aspect_ratio'] );
		} else {
			$attachment_id = ( !empty($instance['attachment_id']) ) ? $instance['attachment_id'] : $instance['image'];
			if ( !empty($attachment_id) ) {
				$image_details = wp_get_attachment_image_src( $attachment_id, 'full' );
				if ($image_details) {
					return ( $image_details[1]/$image_details[2] );
				}
			}
		}
	}

	/**
	 * Loads theme files in appropriate hierarchy: 1) child theme,
	 * 2) parent template, 3) plugin resources. will look in the image-widget/
	 * directory in a theme and the views/ directory in the plugin
	 *
	 * @param string $template template file to search for
	 * @return template path
	 **/

	public function getTemplateHierarchy($template) {
		// whether or not .php was added
		$template_slug = rtrim($template, '.php');
		$template = $template_slug . '.php';

		if ( $theme_file = locate_template(array('image-widget/'.$template)) ) {
			$file = $theme_file;
		} else {
			$file = 'views/' . $template;
		}
		return apply_filters( 'sp_template_image-widget_'.$template, $file);
	}

}
