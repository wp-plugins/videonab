<?php  
$response = false;
if (is_admin() && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crb_request']) && $_POST['crb_request']=='new-video' ) {
	$video_url = $_POST['video_link'];
	$response = VHub_Video::update_single_video( $video_url );
}
?>
<div class="wrap carbon-container">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php echo __('Add New Video', VHub_LangPrefix) ?></h2>

	<?php  
	if ($response) {
		echo '<div class="' . $response['type'] . '"><p>' . $response['msg'] . '</p></div>';
	}
	?>
	
	<form method="post" enctype="multipart/form-data" >
		<table border="0" cellspacing="0" cellpadding="0" class="form-table">
			<tr>
				<th scope="row">
					<label><?php echo __('Video URL', VHub_LangPrefix) ?></label>
				</th>
				<td>
					<input type="text" class="field" value="" name="video_link" />
				</td>
			</tr>
		</table>
		<input type="hidden" value="new-video" name="crb_request" />
		<input type="submit" name="submit" id="submit" class="button-primary" value="Add Video">
	</form>
	
	<hr/>
	<h3><?php echo __('Supported video services:', VHub_LangPrefix) ?></h3>
	<ul>
		<li><img src="<?php echo $this->get_plugin_url('css/images/youtube.png') ?>" alt="" /></li>
	</ul>
	<hr/>
	<h3><?php echo __('Supported video URLs:', VHub_LangPrefix) ?></h3>
	<ul>
		<li>http://www.youtube.com/watch?v=XQu8TTBmGhA</li>
	</ul>
	<hr/>

	<?php  
	if ($response) {
		if ($response['type']=='updated') {
			?><br/><br/>
		    <div class="updated">
		        <p><?php echo __('Video Details', VHub_LangPrefix) ?></p>
		        <table width="100%" border="0" cellspacing="0" cellpadding="0">
		        	<tr>
		        		<td><?php echo __('Title', VHub_LangPrefix) ?></td>
		        		<td><?php echo $response['data']['title'] ?></td>
		        	</tr>
		        	<tr>
		        		<td><?php echo __('Video ID', VHub_LangPrefix) ?></td>
		        		<td><?php echo $response['data']['id'] ?></td>
		        	</tr>
		        	<tr>
		        		<td><?php echo __('Video Description', VHub_LangPrefix) ?></td>
		        		<td><?php echo $response['data']['description'] ?></td>
		        	</tr>
		        	<tr>
		        		<td><?php echo __('Video Thumbnail', VHub_LangPrefix) ?></td>
		        		<td><img src="<?php echo $response['data']['metas']['thumbnail'] ?>" alt="" /></td>
		        	</tr>
		        </table>
		    </div>
			<?php
		}
	}
	?>
</div>