<?php
/**
 * Widget template. This template can be overriden using the "sp_template_image-widget_widget.php" filter.
 * See the readme.txt file for more info.
 */

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

$before_widget = str_replace(' vertical-align', '', $before_widget);
$before_widget = str_replace('<div>', '', $before_widget);
echo $before_widget;

$padding = intval($instance['padding']);
echo '<div style="height: 100%; padding: ' . $padding . 'px; text-align: center;">';
echo $this->get_image_html( $instance, true );
echo '</div>';

if ( !empty( $description ) ) {
	echo '<div class="'.$this->widget_options['classname'].'-description" >';
	echo wpautop( $description );
	echo "</div>";
}
$after_widget = str_replace('</div></div>', '</div>', $after_widget);
echo $after_widget;
?>
