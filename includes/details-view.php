<?php
$enable_comments 	= get_option( $this->prefix . 'enable_comments' )=='Yes';
?>
<div class="crbh-videos crbh-videos-details">
	<?php
	global $post;
	$video_service = get_post_meta($post->ID,'video_service',true);
	$template_file = $this->get_plugin_path('includes/details-view-' . $video_service . '.php');
	if ( file_exists( $template_file ) ) {

		$title 			= $post->post_title;
		$video_data 	= VHub_Video::get_video_data( $post->ID );
		$embeded_link 	= VHub_Video::get_video_embeded_link( $video_data['id'] );
		$link 			= get_permalink($post->ID);
		$id 			= $post->ID;

		VHub_Video_Nav_Links::add_filters();

		ob_start();
			next_post_link('%link', __('Previous', VHub_LangPrefix));
		$prev_link = ob_get_clean();

		ob_start();
		 	previous_post_link('%link', __('Next', VHub_LangPrefix));
		$next_link = ob_get_clean();

		VHub_Video_Nav_Links::remove_filters();
		?>
		<div class="crbh-navigation <?php echo ($prev_link && $next_link) ? 'two-buttons' : 'one-button' ?>">
			<a class="crbh-back crbh-left crbh-block" href="<?php echo $this->get_video_page() ?>"><span>&nbsp;</span></a>
			<span class="crbh-title crbh-left crbh-block">
				<span><?php echo $title ?></span>
			</span>

			<?php if ($prev_link): ?>
				<span class="crbh-prev crbh-left crbh-block" ><?php echo $prev_link ?></span>
			<?php endif ?>

			<?php if ($next_link): ?>
				<span class="crbh-next crbh-left crbh-block" ><?php echo $next_link ?></span>
			<?php endif ?>
		</div>

		<div class="crbh-video-holder">
			<?php include( $template_file ); ?>
		</div>

		<div class="crbh-content">
			<?php
			// add block video button
			include( $this->get_plugin_path('includes/block_video_link.php') );
			?>
			<?php echo wpautop($post->post_content); ?>
		</div>

		<?php
		if ($enable_comments) {
			?>
			<div class="crbh-comments" data-id="<?php echo $post->ID ?>" >
				<div class="fb-comments" data-href="<?php echo get_permalink(); ?>" data-width="100%" data-numposts="3" data-colorscheme="light"></div>
			</div>
			<?php
		}
		?>

		<?php
	}else{
		?>
		<p><?php echo __('Unsupported Video Service', VHub_LangPrefix) ?></p>
		<p><?php echo __('Missing Template File', VHub_LangPrefix) ?></p>
		<?php
	}
	?>
</div>