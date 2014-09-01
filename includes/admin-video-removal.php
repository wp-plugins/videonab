<div class="wrap carbon-container">
	<script type="text/javascript">
		jQuery(function($) {
			var response_contaner = $('.carbon-response');
			function form_ajax( form ){
				$.ajax({
					type: 	'POST',
					url: 	form.attr('action'),
					data: 	form.serializeArray(),
					success: function(response) {
						if ( $('.carbon-response > .carbon-msg' ,response).length ) {
							var content = $('.carbon-response > *', response);
							response_contaner.append( content );
							form_ajax( form );
						}else{
							response_contaner.append('<div class="carbon-msg carbon-updated"><p>Done.</p></div>');
						};
					},
					error: function () {
						alert("Couldn't complete your request.  Please try again later or contact us if the persist.");
					}
				});
			}

			$('.carbon-container form').on('submit', function(e){
				response_contaner.append('<div class="carbon-msg carbon-updated"><p>Loading... Please Wait</p></div>');

				form_ajax( $(this) );

				e.preventDefault();
			});
		});
	</script>
	<style type="text/css" media="screen">
		.carbon-container .carbon-response {
			max-height: 400px;
			max-width: 800px;
			overflow-y: scroll;
		}
		div.carbon-response {
			padding:0 10px 0 0;
			margin-top:20px
		}
		div.carbon-response div.carbon-updated,
		div.carbon-response div.carbon-error {
			margin: 5px 0 15px;
			border-left: 4px solid #7ad03a;
			padding: 1px 12px;
			background-color: #fff;
			-webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
			box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
		}
		div.carbon-response div.carbon-error {
			border-left: 4px solid #dd3d36
		}
	</style>
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php echo __('Video Removal', VHub_LangPrefix) ?></h2>

	<form method="post" enctype="multipart/form-data" >
		<br/>
		<input type="hidden" value="remove-videos" name="crb_request" />
		<input type="submit" name="submit" id="submit" class="button-primary" value="Delete all videos">
	</form>

	<div class="carbon-response">
		<?php  
		if (is_admin() && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crb_request']) && $_POST['crb_request']=='remove-videos' ) {
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