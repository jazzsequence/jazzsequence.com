// Stuff that happen on the post page

function yourls_update_count() {
	var len = 140 - jQuery('#titlewrap #title').val().length;
	jQuery('#yourls_count').html(len);
	jQuery('#yourls_count').removeClass();
	if (len < 60) {jQuery('#yourls_count').removeClass().addClass('len60');}
	if (len < 30) {jQuery('#yourls_count').removeClass().addClass('len30');}
	if (len < 15) {jQuery('#yourls_count').removeClass().addClass('len15');}
	if (len < 0) {jQuery('#yourls_count').removeClass().addClass('len0');}
}

(function($){
	var yourls = {
		// Send a tweet
		send: function() {
		
			var post = {};
			post['yourls_tweet'] = $('#yourls_tweet').val();
			post['yourls_post_id'] = $('#yourls_post_id').val();
			post['yourls_twitter_account'] = $('#yourls_twitter_account').val();
			post['action'] = 'yourls-promote';
			post['_ajax_nonce'] = $('#_ajax_yourls').val();

			$('#yourls-promote').html('<p>Please wait...</p>');

			$.ajax({
				type : 'POST',
				url : ajaxurl,
				data : post,
				success : function(x) { yourls.success(x, 'yourls-promote'); },
				error : function(r) { yourls.error(r, 'yourls-promote'); }
			});
		},
		
		// Reset short URL
		reset: function() {
		
			var post = {};
			post['yourls_post_id'] = $('#yourls_post_id').val();
			post['yourls_shorturl'] = $('#yourls_shorturl').val();
			post['action'] = 'yourls-reset';
			post['_ajax_nonce'] = $('#_ajax_yourls').val();

			$('#yourls-shorturl').html('<p>Please wait...</p>');

			$.ajax({
				type : 'POST',
				url : ajaxurl,
				data : post,
				success : function(x) { yourls.success(x, 'yourls-shorturl'); yourls.update(x); },
				error : function(r) { yourls.error(r, 'yourls-shorturl'); }
			});
		},
		
		// Update short URL in the tweet textarea
		update: function(x) {
			var r = wpAjax.parseAjaxResponse(x);
			r = r.responses[0];
			var oldurl = r.supplemental.old_shorturl;
			var newurl = r.supplemental.shorturl;
			var bg = jQuery('#yourls_tweet').css('backgroundColor');
			if (bg == 'transparent') {bg = '#fff';}

			$('#yourls_tweet')
				.val( $('#yourls_tweet').val().replace(oldurl, newurl) )
				.animate({'backgroundColor':'#ff8'}, 500, function(){
					jQuery('#yourls_tweet').animate({'backgroundColor':bg}, 500)
				});
		},
		
		// Ajax: success
		success : function(x, div) {
			if ( typeof(x) == 'string' ) {
				this.error({'responseText': x}, div);
				return;
			}

			var r = wpAjax.parseAjaxResponse(x);
			if ( r.errors )
				this.error({'responseText': wpAjax.broken}, div);

			r = r.responses[0];
			$('#'+div).html('<p>'+r.data+'</p>');
			
			console.log( r.supplemental.shorturl );
			
			//Update also built-in Shortlink button
			$('#shortlink').val( r.supplemental.shorturl );
		},

		// Ajax: failure
		error : function(r, div) {
			var er = r.statusText;
			if ( r.responseText )
				er = r.responseText.replace( /<.[^<>]*?>/g, '' );
			if ( er )
				$('#'+div).html('<p>Error during Ajax request: '+er+'</p>');
		}
	};
	
	$(document).ready(function(){
		// Add the character count
		jQuery('#titlewrap #title').after('<div id="yourls_count" title="Number of chars remaining in a Twitter environment">000</div>').keyup(function(e){
			yourls_update_count();
		});
		yourls_update_count();

		$('#yourls_promote').click(function(e) {
			yourls.send();
			e.preventDefault();
		});
		$('#yourls_reset').click(function(e) {
			yourls.reset();
			e.preventDefault();
		});
		
	})

})(jQuery);