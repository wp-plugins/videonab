<?php  
if (current_user_can('administrator') && isset($id) && is_numeric($id) ) {
	?>
	<div class="crbh-block-container">
		<a class="crbh-block-link" href="<?php echo remove_query_arg( 'video_page', add_query_arg( array('block_video' => $id) ) ); ?>"><?php echo __('Block this video', VHub_LangPrefix) ?></a>
	</div>
	<?php
}