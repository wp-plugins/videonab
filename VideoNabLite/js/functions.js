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

	});

})(jQuery, window, document);