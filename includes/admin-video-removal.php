<div class="wrap carbon-container crbh-ajax">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php echo __('Video Removal', VHub_LangPrefix) ?></h2>

	<form method="post" enctype="multipart/form-data" >
		<br/>
		<input type="hidden" value="remove-videos" name="crb_request" />
		<input type="submit" name="submit" id="submit" class="button-primary" value="Delete all videos">
	</form>

	<div class="carbon-response">
		<?php  
		if (
			is_admin() 
			&& is_user_logged_in()
			&& $_SERVER['REQUEST_METHOD'] == 'POST' 
			&& !empty($_POST['crb_request']) 
			&& $_POST['crb_request']=='remove-videos' 
		) {
			$videos_to_remove = get_posts( array(
					'post_type' => $this->post_type,
					'posts_per_page' => 20
				) );
			foreach ($videos_to_remove as $entry) {
				if ( wp_delete_post( $entry->ID, true ) ) {
					echo '<div class="carbon-msg carbon-updated"><p>' . $entry->post_title . ' ::: has been deleted</p></div>';
				}else{
					echo '<div class="carbon-msg carbon-error"><p>' . $entry->post_title . ' ::: could not be deleted</p></div>';
				}
			}
		}
		?>
	</div>
</div>