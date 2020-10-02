/**
 * story|ftw - v0.1.0 - 2015-02-28
 * http://storyftw.com
 *
 * Copyright (c) 2015;
 * Licensed GPLv2+
 */
window.StoryFTW = window.StoryFTW || {};

window.StoryFTW.Utils = (function(window, document, $, undefined){
	'use strict';

	var l10n = window.StoryFTW_l10n;

	function Selector_Cache() {
		var elementCache = {};

		var get_from_cache = function( selector, $ctxt, reset ) {

			if ( 'boolean' === typeof $ctxt ) { reset = $ctxt; }
			var cacheKey = $ctxt ? $ctxt.selector + ' ' + selector : selector;

			if ( undefined === elementCache[ cacheKey ] || reset ) {
				elementCache[ cacheKey ] = $ctxt ? $ctxt.find( selector ) : jQuery( selector );
			}
			return elementCache[ cacheKey ];
		};
		return get_from_cache;
	}

	var app = {};

	app.log = function() {
		app.log.logHistory = app.log.logHistory || [];
		app.log.logHistory.push( arguments );
		if ( window.console && l10n.debug ) {
			window.console.log( Array.prototype.slice.call( arguments) );
		}
	};

	app.cache = new Selector_Cache();

	return app;

})(window, document, jQuery);

window.StoryFTW = window.StoryFTW || {};

(function( window, document, $, app, undefined ){
	'use strict';

	var l10n   = window.StoryFTW_l10n;
	// var log    = window.StoryFTW.Utils.log;
	var cache$ = window.StoryFTW.Utils.cache;

	app.advancedToggle = function() {

		cache$( '.advanced-toggle' ).children('small.toggle-label, .handlediv').click(function(){
			var $this = $(this);
			var $parents = $this.parents( '.advanced-toggle' );
			if ( $parents.hasClass( 'closed' ) ) {
				$parents.removeClass( 'closed' );
				$this.siblings( '.inside' ).show();
			} else {
				$parents.addClass( 'closed' );
				$this.siblings( '.inside' ).hide();
			}
		});
	};

	if ( l10n.storyID ) {
		var addStoryURLstring = 'associated_story_id='+ l10n.storyID;

		$.fn.addStoryID = function() {
			return this.each(function() {
				var $this = $(this);
				var oldhref = $this.attr( 'href' );
				var newhref = oldhref.search(/\?/i) ? oldhref + '&'+ addStoryURLstring : oldhref + '?' + addStoryURLstring;
				$this.attr( 'href', newhref );
			});
		};

	}

})(window, document, jQuery, window.StoryFTW);

( function( window, document, $, app, undefined ) {
	'use strict';

	var l10n   = window.StoryFTW_l10n;
	var log    = window.StoryFTW.Utils.log;
	var cache$ = window.StoryFTW.Utils.cache;

	app.init = function() {
		log( l10n );
		log( cache$ );

		$( 'a[href*="'+ l10n.toReplace +'"]' ).addStoryID();
		app.advancedToggle();
	};

	$(document).ready( app.init );

	return app;

})( window, document, jQuery, window.StoryFTW );
