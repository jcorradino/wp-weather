<?php
	echo get_template_directory();
?>






<section class="wp_weather">
	<?php if ($instance['widget_title'] != "") { ?>
		<h1 class="content-headline"><?php echo $instance['widget_title']; ?></h1>
	<?php } ?>
	<h3 class="location"><?php echo $conditions->current_observation->display_location->full?></h3>
	<img src="<?php echo $image_path.$conditions->current_observation->icon; ?>.gif" title="<?php echo $conditions->current_observation->weather; ?>">
	<span class="current-conditions"><?php echo $conditions->current_observation->weather; ?></span>
	<span class="temp"><?php echo round($conditions->current_observation->temp_f); ?>&deg; F</span>
</section>