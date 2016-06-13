<?php
/**
 * Image widget admin template
 */

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

	$id_prefix = $this->get_field_id('');
?>
<div class="uploader">
	<input type="submit" class="button" name="<?php echo $this->get_field_name('uploader_button'); ?>" id="<?php echo $this->get_field_id('uploader_button'); ?>" value="<?php _e('Select an Image', 'wpds-image'); ?>" onclick="imageWidget.uploader( '<?php echo $this->id; ?>', '<?php echo $id_prefix; ?>' ); return false;" />
	<div class="tribe_preview" id="<?php echo $this->get_field_id('preview'); ?>">
		<?php echo $this->get_image_html($instance, false); ?>
	</div>
	<input type="hidden" id="<?php echo $this->get_field_id('attachment_id'); ?>" name="<?php echo $this->get_field_name('attachment_id'); ?>" value="<?php echo abs($instance['attachment_id']); ?>" />
	<input type="hidden" id="<?php echo $this->get_field_id('imageurl'); ?>" name="<?php echo $this->get_field_name('imageurl'); ?>" value="<?php echo $instance['imageurl']; ?>" />
</div>
<br clear="all" />

<div id="<?php echo $this->get_field_id('fields'); ?>" <?php if ( empty($instance['attachment_id']) && empty($instance['imageurl']) ) { ?>style="display:none;"<?php } ?>>
	<p><label for="<?php echo $this->get_field_id('padding'); ?>"><?php _e('Padding', 'wpds-image'); ?>:</label>
		<input class="widefat" id="<?php echo $this->get_field_id('padding'); ?>" name="<?php echo $this->get_field_name('padding'); ?>" type="number" value="<?php echo esc_attr(strip_tags($instance['padding'])); ?>" /></p>
</div>
