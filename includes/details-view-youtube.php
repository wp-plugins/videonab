<div class="crbh-video">
	<div id="ytplayer"><?php echo __('You need Flash Player 8+ and JavaScript enabled to view this video.', VHub_LangPrefix) ?></div>

	<script type="text/javascript" language="javascript">
		var videoId = '<?php echo $video_data['id']; ?>';
		var player_id = 'ytplayer';

		if ( crbh_isFlashEnabled() ) {
			var params = { allowScriptAccess: "always", allowFullScreen: true, wmode: "transparent" };
			var atts = { id: player_id };
			swfobject.embedSWF("http://www.youtube.com/e/" + videoId + "?enablejsapi=1&playerapiid=ytplayer", player_id, "638", "380", "8", null, null, params, atts);
		} else {
			var tag = document.createElement('script');

			tag.src = "https://www.youtube.com/iframe_api";
			var firstScriptTag = document.getElementsByTagName('script')[0];
			firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

			var player;

			function onYouTubeIframeAPIReady() {
				player = new YT.Player(player_id, {
					height: '380',
					width: '638',
					videoId: videoId,
					events: {
						'onReady': crbh_onYouTubePlayerReady
					}
				});
			}
		};

		function crbh_onYouTubePlayerReady( playerId_or_event ) {
			if ( typeof(playerId_or_event)==='object' ) {
				player.cueVideoById(videoId, 0, 'hd720');
			} else {
				player = document.getElementById( playerId_or_event );
				player.cueVideoById(videoId, 0, 'hd720');
			};

			player.setPlaybackQuality( 'hd720' );

			j$ = jQuery;

			j$('#player-720p').click(function() {
				var time = player.getCurrentTime();
				player.loadVideoById(videoId, time, 'hd720');
				player.setPlaybackQuality( 'hd720' );
				return false;
			});

			j$('#player-1080p').click(function() {
				var time = player.getCurrentTime();
				player.loadVideoById(videoId, time, 'hd1080')
				player.setPlaybackQuality( 'hd1080' );
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