;(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);

	$doc.ready(function() {

		$('.crbh-list .crbh-row').on('click', function(e){
			var $this = $(this),
				href = $this.attr('data-href');

			if ( href && !$(e.target).hasClass('st_sharethis_custom') ) {
				window.location.href = href;
			};
		});

		/**
		*	Facebook Like/Comments js handler
		*/
		window.fbAsyncInit = function () {
			var $ = jQuery;

			// handles FB like button
			FB.Event.subscribe('edge.create', handleLikeButton);
			FB.Event.subscribe('edge.remove', handleLikeButton);

			// handles FB comments
			FB.Event.subscribe('comment.create', handleComment);
			FB.Event.subscribe('comment.remove', handleComment);

			function handleLikeButton(response) {
				var item = $('div.crbh-rating[data-url="' + response + '"]');
				var videoId = item.data('id');

				$.post(window.vhub_website_url, { scr_action: 'like_count_update', id: videoId }, function (data, status, XHR) {
					// success code goes here
					/*
					if (status == 'success') {
						place the code here
					}
					*/
				});
			}

			function handleComment(response) {
				var videoId = $('.crbh-comments').data('id');

				$.post(window.location.href, { scr_action: 'comment_count_update', id: videoId }, function (data, status, XHR) {
					// success code goes here
					/*
					if (status == 'success') {
						place the code here
					}
					*/
				});
			}
		}

		// fixing the issue when the function is already defined in the theme
		if ( typeof onYouTubePlayerReady === 'function' ) {
			var theme_onYouTubePlayerReady = onYouTubePlayerReady;
			onYouTubePlayerReady = function(playerId_or_event) {
				crbh_onYouTubePlayerReady(playerId_or_event);
				theme_onYouTubePlayerReady(playerId_or_event)
			};
		} else {
			onYouTubePlayerReady = function(playerId_or_event) {
				crbh_onYouTubePlayerReady(playerId_or_event);
			};
		}
	});

})(jQuery, window, document);

//checks if flash is installed/enabled on the browser
function crbh_isFlashEnabled() {
	var hasFlash = false;

	try {
		var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');

		if(fo) {
			hasFlash = true;
		}
	} catch(e) {
		if(navigator.mimeTypes ["application/x-shockwave-flash"] != undefined) {
			hasFlash = true;
		}
	}

	return hasFlash;
}