<div class="wrap carbon-container crbh-ajax">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php echo __('Video Updater', VHub_LangPrefix) ?></h2>

	<form method="post" enctype="multipart/form-data" >
		<br/>
		<input type="hidden" value="update-videos" name="crb_request" />
		<input type="submit" name="submit" id="submit" class="button-primary" value="Update all videos">
	</form>

	<?php  
	$current_page = !empty( $_POST['crb_paged'] ) ? (int) $_POST['crb_paged'] : 1;
	?>
	<div class="carbon-response" data-paged="<?php echo $current_page ?>" >
		<?php  

		if (
			is_admin() 
			&& is_user_logged_in()
			&& $_SERVER['REQUEST_METHOD'] == 'POST' 
			&& !empty($_POST['crb_request']) 
			&& $_POST['crb_request']=='update-videos' 
		) {
			$videos_to_remove = get_posts( array(
					'post_type' => $this->post_type,
					'posts_per_page' => 20,
					'paged' => $current_page
				) );
			foreach ($videos_to_remove as $entry) {
				$video_url = 'https://www.youtube.com/watch?v=' . get_post_meta($entry->ID, 'video_id', true);
				$response = VHub_Video::update_single_video( $video_url );

				// update video
				if ( $response['type']=='updated' ) {
					?>
					<div class="carbon-msg carbon-updated">
						<p><?php echo $entry->post_title ?> ::: <?php echo __('has been updated', VHub_LangPrefix) ?></p>
					</div>
					<?php
				}else{
					?>
					<div class="carbon-msg carbon-error">
						<p><?php echo $entry->post_title ?> ::: <?php echo __( 'could not be updated', VHub_LangPrefix) ?></p>
						<p><?php echo $response['msg'] ?></p>
					</div>
					<?php
				}
			}
		}
		?>
	</div>
</div>