jQuery(function($) {

	var $win = $(document),
		$doc = $(document),
		$filter_field = $('select[name=crbh_date_filter]'),
		$filter_fixed = $('div[data-name=crbh_date_filter_fixed]').parents('tr'),
		$filter_before = $('div[data-name=crbh_date_filter_before]').parents('tr'),
		$filter_after = $('div[data-name=crbh_date_filter_after]').parents('tr'),
		$filter_within_days = $('div[data-name=crbh_date_filter_last_days]').parents('tr'),
		$response_contaner = $('.carbon-response'),
		can_ajax = true;

	$doc.on('ready', function() {

		init_date_filter();

		init_grab_more_videos();

		init_crbh_ajax_forms();

		init_dropdown_tooltips();

	});

	function init_date_filter(){

		animate_filter_fields( $filter_field.val() );

		$filter_field.on('change', function( event ) {
			var value = $(this).val();

			animate_filter_fields( value, true );
		});
	}

	function animate_filter_fields( filter_type, clean_field_value ) {
		clean_field_value = typeof clean_field_value !== 'undefined' ? clean_field_value : false;

		$filter_before.hide();
		$filter_after.hide();
		$filter_fixed.hide();
		$filter_within_days.hide();

		if ( clean_field_value ) {
			$filter_before.find('input').val('');
			$filter_after.find('input').val('');
			$filter_fixed.find('input').val('');
			$filter_within_days.find('input').val('');
		};

		switch( filter_type ) {
			case 'specific_date':
				$filter_fixed.show();
				break;
			case 'within_last_days':
				$filter_within_days.show();
				break;
			case 'after_specific_date':
				$filter_after.show();
				break;
			case 'before_specific_date':
				$filter_before.show();
				break;
			case 'between_specific_date':
				$filter_before.show();
				$filter_after.show();
				break;
			case 'any_date':
			default:
				//
				break;
		}
	}

	function form_ajax( form, current_page ){

		if ( !can_ajax ) {
			return;
		};

		can_ajax = false;

		var current_page = typeof current_page !== 'undefined' ? parseInt(current_page) : false,
			form_data = form.serializeArray();

		// add paged
		if ( current_page ) {
			form_data.push({ name: "crb_paged", value: current_page });
		};

		$.ajax({
			type: 'POST',
			url: form.attr('action'),
			data: form_data,
			success: function(response) {

				if ( $('.carbon-response > .carbon-msg' ,response).length ) {
					var content = $('.carbon-response > *', response);
					$response_contaner.append( content );


					setTimeout(function(){
						can_ajax = true;

						if ( current_page ) {

							$response_contaner.attr('data-paged', (current_page+1) );

							form_ajax( form, (current_page+1) );
						} else {
							form_ajax( form );
						};
					}, 5000);

				}else{
					can_ajax = true;

					$response_contaner.append('<div class="carbon-msg carbon-updated"><p>Done.</p></div>');
				};
			},
			error: function () {
				can_ajax = true;

				alert("Couldn't complete your request.  Please try again later or contact us if the persist.");
			}
		});
	}

	function init_crbh_ajax_forms() {
		$('.carbon-container.crbh-ajax form').on('submit', function(e){
			$response_contaner.append('<div class="carbon-msg carbon-updated"><p>Loading... Please Wait</p></div>');

			var current_page = $response_contaner.attr('data-paged');

			form_ajax( $(this), current_page );

			e.preventDefault();
		});
	}

	function init_grab_more_videos() {
		var btn_selector = 'span.button.button-large.crbh-grab-videos',
			$recurrence_field = $('select[name=crbh_cron_recurrence]');

		// $recurrence_field.after( $(btn_selector) );

		var $btn = $(btn_selector);

		if ( !$btn.length ) {
			return;
		};

		$btn.on('click', function() {
			grab_more_videos();
		});
	}

	function grab_more_videos( page, page_token ) {
		if ( !can_ajax ) {
			return;
		};

		var page = typeof page !== 'undefined' ? parseInt(page) : 0;
		var page_token = typeof page_token !== 'undefined' ? page_token : null;

		var $btn = $('span.button.button-large.crbh-grab-videos');
		if ( !$btn.length ) {
			return;
		};

		if ( page===0 ) {
			$response_contaner.append('<div class="carbon-msg carbon-updated"><p>Loading... Please Wait</p></div>');
		};

		can_ajax = false;

		$.post( $btn.attr('data-url'), {
			'action': 'grab_more_videos',
			'current_page_page' : page,
			'page_token' : page_token
		}, function( response ){
			can_ajax = true;

			try { // test for json
				var data = $.parseJSON(response);

				if ( Object.prototype.toString.call( data.response ) === '[object Array]' ) {
					$.each(data.response, function(index, field_data){
				  		$response_contaner.append( field_data.html );
					});
				}

				if ( data.proceed===true ) {
					grab_more_videos(page+1, data.next_page_token);
				} else {
					$response_contaner.append( '<div class="carbon-msg carbon-updated"><p>Done</p></div>' );
				};
			} catch( e ) {
				console.log(response)
				alert('Something went wrong. Please, try again later.');
			}
		} );
	}

	function init_dropdown_tooltips() {
		$('.crbh-tooltips-btn').on( 'click', function() {
			var $parent = $(this).parents('.carbon-select-extended-vh');

			if ( !$parent.hasClass('crbh-expanded') ) {
				$('.carbon-select-extended-vh').removeClass('crbh-expanded');
				$('.carbon-select-extended-vh .crbh-tooltips').addClass('crbh-hidden');
			};

			$parent.toggleClass('crbh-expanded');
			$parent.find('.crbh-tooltips').toggleClass('crbh-hidden');
		} );
	}

});