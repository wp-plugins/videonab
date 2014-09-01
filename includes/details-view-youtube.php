<div class="crbh-video">
	<div id="ytplayer"><?php echo __('You need Flash Player 8+ and JavaScript enabled to view this video.', VHub_LangPrefix) ?></div>
	<script type="text/javascript" language="javascript">
		var params = { allowScriptAccess: "always", allowFullScreen: true, wmode: "transparent" };
		var atts = { id: "ytplayer" };
		swfobject.embedSWF("http://www.youtube.com/e/<?php echo $video_data['id']; ?>?enablejsapi=1&playerapiid=ytplayer", "ytplayer", "638", "380", "8", null, null, params, atts);
		
		function onYouTubePlayerReady(playerId) {
			var videoId = '<?php echo $video_data['id']; ?>';
			player = document.getElementById(playerId);
			player.cueVideoById(videoId, 0, 'hd720');
			
			j$ = jQuery;
			
			j$('#player-720p').click(function() {
				var time = player.getCurrentTime();
				player.loadVideoById(videoId, time, 'hd720');
				return false;
			});

			j$('#player-1080p').click(function() {
				var time = player.getCurrentTime();
				player.loadVideoById(videoId, time, 'hd1080');
				return false;
			});
		}
	</script>
</div>
<div class="crbh-video-options">
	<div class="crbh-share crbh-left crbh-block">
		<span>
			<span 
				class="st_sharethis_custom crbh-sharehis" 
				st_title="<?php echo esc_attr( $post->post_title ); ?>" 
				st_url="<?php echo $link; ?>" 
				displayText="<?php echo esc_attr( $video_data['short_description'] ); ?>" 
			><?php echo __('Share This Video', VHub_LangPrefix) ?></span>
		</span>
	</div>
	<a class="crbh-rez-one crbh-left crbh-block" href="#" id="player-720p" >720P</a>
	<a class="crbh-rez-two crbh-left crbh-block" href="#" id="player-1080p" >1080P</a>
</div>