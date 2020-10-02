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

/*jslint browser: true */
/*global jQuery:false */

window.StoryFTW = window.StoryFTW || {};

window.StoryFTW.app = (function(window, document, $, undefined) {
	'use strict';

	var l10n   = window.StoryFTW_l10n;
	var log    = window.StoryFTW.Utils.log;
	var cache$ = window.StoryFTW.Utils.cache;
	var app    = {
		is_touch : Modernizr.touch,
		flipsnap : new Flipsnap( '.storybook', { transitionDuration: 600 } ),
		queued   : false,  // video to be played next
		videos   : [],     // array of video elements
		index    : 0,      // current location in storybook
		index_names : l10n.index_names,
		names_index : l10n.names_index
	};
	var keys   = { leftarrow : 37, rightarrow : 39, uparrow : 38, downarrow : 40, escape : 27 };

	app.keydown = function( evt ) {
		var keyTyped = evt.keyCode;
		if ( keyTyped === keys.leftarrow ) {
			app.closeModals();
			app.flipsnap.toPrev();
			return false;
		}
		if ( keyTyped === keys.rightarrow ) {
			app.closeModals();
			app.flipsnap.toNext();
			return false;
		}
		if ( keyTyped === keys.uparrow || keyTyped === keys.downarrow ) {
			evt.preventDefault();
			app.closeModals();
			if ( keyTyped === keys.uparrow ) {
				app.toggleMenu();
			} else {
				app.hideMenu();
			}
		}
		if ( keyTyped === keys.escape ) {
			evt.preventDefault();
			app.closeModals();
			app.hideMenu();
		}
	};

	app.closeModals = function() {
		cache$( '.js-modal' ).fadeOut().find( '.js-embed-wrap' ).empty();
	};

	app.hideMenu = function() {
		cache$( '.shifty' ).removeClass( 'show-menu' );
	};

	app.showMenu = function() {
		cache$( '.shifty' ).addClass( 'show-menu' );
	};

	app.toggleMenu = function() {
		app.closeModals();
		if ( cache$( '.shifty' ).hasClass( 'show-menu' ) ) {
			app.hideMenu();
		} else {
			app.showMenu();
		}
	};

	app.hex2rgb = function( color ) {
		if ( ! color ) {
			return '';
		}

		return {
			r : parseInt( color.slice( -6, -4 ), 16 ),
			g : parseInt( color.slice( -4, -2 ), 16 ),
			b : parseInt( color.slice( -2 ), 16 )
		};

	};

	/**
	 * Converts an hex color value to HSL. Conversion formula
	 * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
	 * Assumes r, g, and b are contained in the set [0, 255] and
	 * returns h, s, and l in the set [0, 1].
	 */
	app.rgb2hsl = function( rgb, luminenceOnly ) {
		var r   = rgb.r / 255;
		var g   = rgb.g / 255;
		var b   = rgb.b / 255;
		var max = Math.max( r, g, b );
		var min = Math.min( r, g, b );
		var h, s, l = ( max + min ) / 2;

		if ( max === min ){
			h = s = 0; // achromatic
		} else {
			var d = max - min;
			s = l > 0.5 ? d / ( 2  - max - min) : d / ( max + min );
			switch( max ){
				case r: h = ( g - b ) / d + ( g < b ? 6 : 0 ); break;
				case g: h = ( b - r ) / d + 2; break;
				case b: h = ( r - g ) / d + 4; break;
			}
			h /= 6;
		}

		var hsl = { h : h, s : s, l : l };
		if ( luminenceOnly ) {
			return hsl.l;
		}

		return hsl;
	};

	$.fn.bgrgb = function( rgb, opacity ) {
		if ( ! rgb ) {
			return this;
		}

		var rgbaCol = rgb.r + ', ' + rgb.g + ', ' + rgb.b;

		if ( opacity ) {
			rgbaCol += ', '+ opacity;
		}

		return $( this ).css({ 'background-color': 'rgba( '+ rgbaCol +' )' });
	};

	$.fn.showOrHide = function( toCheck ) {

		var show  = app.storyPageData[ toCheck ];
		var $this = $( this );
		app.storyPageData[ toCheck ] = show ? show : l10n.story[ toCheck ];

		var exists = app.storyPageData[ toCheck ] && ! ( 'footertitleshow' === toCheck && cache$( window ).width() < 769 );

		if ( exists ) {
			$this.removeClass( 'super-hide' );
			app.showFooter = true;
		} else {
			$this.addClass( 'super-hide' );
		}

		return this;
	};

	app.createVideos = function() {
		app.$storyPages.each( function() {
			var $this = $( this );
			var data = $this.data( 'storymeta' );
			var has_video = data && data.mp4;
			var $new_video;

			if ( has_video ) {
				$new_video = $( '<video></video>' ).attr( 'muted', true );
			}
			app.videos.push( $new_video );
		});
	};

	app.unloadVideos = function() {
		app.$storyPages.each( function( i ) {
			if ( i < ( app.index - 2 ) || i > ( app.index + 2 ) ) {
				$( app.$storyPages[i] ).find( 'video' ).detach();
			}
		});
	};

	app.loadVideo = function( i ) {
		var $thisStoryPage = $( app.$storyPages[i] );
		var thisVideo      = app.videos[i];
		var container      = $thisStoryPage.find( '.bg-video-wrap' )[0];
		var $thisVideo     = $( thisVideo ).html( '' );
		// var $videoSource   = $thisVideo.find( 'source' );

		if ( ! container ) {
			return;
		}


		var mp4  = app.storyPageData.mp4;
		var webm = app.storyPageData.webm;
		var ogv  = app.storyPageData.ogv;
		var html = '';

		if ( mp4 ) {
			html += '<source src="'+ mp4 +'">';
		}
		if ( webm ) {
			html += '<source src="'+ webm +'">';
		}
		if ( ogv ) {
			html += '<source src="'+ ogv +'">';
		}

		$thisVideo.append( html );

		thisVideo.load();

		$thisVideo.appendTo(container);

	};

	app.playVideo = function() {
		if ( app.video ) {
			app.video.pause();
		}
		if ( app.queued ) {
			app.video = app.queued;
		}
		if ( app.video ) {
			app.video.play();
			$( app.video ).bind( 'timeupdate', function() {
				if ( app.video.currentTime > app.video.duration - 0.3 ) {
					app.video.currentTime = 0;
					app.video.play();
				}
			});
		}
	};

	app.triggerVideo = function() {
		if ( ! app.is_touch ) {
			window.clearTimeout( app.vidTimeout );
			app.unloadVideos();
			app.loadVideo( app.index );
			app.loadVideo( app.index + 1 );
			app.loadVideo( app.index - 1 );
			app.queued = app.$storyPage.find( 'video' )[0];
			app.vidTimeout = window.setTimeout( function() {
				app.playVideo();
			}, 20 );
		}
	};

	app.updateNav = function() {
		if ( cache$( '.js-story-page-previous' ).is( ':visible' ) || cache$( '.js-story-page-next' ).is( ':visible' ) ) {
			if ( ! app.flipsnap.hasNext() ) {
				cache$( '.js-story-page-next' ).addClass( 'hide' );
			} else if ( !cache$( '.js-story-page-next' ).is( ':visible' )) {
				cache$( '.js-story-page-next' ).removeClass( 'hide' );
			}
			if ( ! app.flipsnap.hasPrev() ) {
				cache$( '.js-story-page-previous' ).addClass( 'hide' );
				$( '.js-coach-mark' ).removeClass( 'hide' ).addClass( 'table-cell' );
			} else if ( !cache$( '.js-story-page-previous' ).is( ':visible' ) ) {
				cache$( '.js-story-page-previous' ).removeClass( 'hide' );
				$( '.js-coach-mark' ).removeClass( 'table-cell' ).addClass( 'hide' );
			} else if ( $( '.js-coach-mark' ).is( ':visible' ) ) {
				$( '.js-coach-mark' ).removeClass( 'table-cell' ).addClass( 'hide' );
			}
		}
	};

	app.updateColors = function() {
		var bg = app.storyPageData.color;
		if ( ! bg ) {
			return;
		}

		var rgb = app.hex2rgb( bg );

		cache$( '.bg-dynamic' ).css({ 'background-color': bg });
		cache$( '.bg-dynamic-a' ).bgrgb( rgb, '.75' );

		// app.lightOrDark( app.rgb2hsl( rgb, true ) > 0.8 );
		app.lightOrDark( 'light' !== app.storyPageData.textcolor );
	};

	/*app.lightOrDark = function( dark ) {
		if ( dark ) {
			$('.light, body a').removeClass( 'light' ).addClass( 'dark' );
		} else {
			$('.dark, body a').removeClass( 'dark' ).addClass( 'light' );
		}
	};*/

	app.lightOrDark = function( dark ) {
		if ( dark ) {
			$('.story-page .light').removeClass( 'light' ).addClass( 'dark' );
		} else {
			$('.story-page .dark').removeClass( 'dark' ).addClass( 'light' );
		}
	};

	app.editLink = function( postID ) {
		if ( ! cache$( '#wp-admin-bar-storyftw-edit-page' ).length ) {
			return;
		}

		var $link = cache$( '> .ab-item', cache$( '#wp-admin-bar-storyftw-edit-page' ) );
		var replace = cache$( 'span', $link ).data( 'href' );
		$link.attr( 'href', replace.replace( 'idreplace', postID ) );
	};

	app.footerOverrides = function() {

		var $navBar = cache$( '.sftw-navbar' );

		app.showFooter = false;
		cache$( '.footer-text-color.center', $navBar ).showOrHide( 'footertitleshow' );
		cache$( '.js-shifty-toggle.btn', $navBar ).showOrHide( 'tocshow' );
		cache$( '.facebook-share-button', $navBar ).showOrHide( 'fbshow' );
		cache$( '.twitter-share-button', $navBar ).showOrHide( 'twshow' );

		if ( ! app.showFooter ) {
			$navBar.addClass( 'super-hide' );
		} else {
			$navBar.removeClass( 'super-hide' );
		}
	};

	app.colorOverrides = function() {

		// log( 'app.storyPageData', app.storyPageData );

		if ( app.storyPageData.footertextcolor ) {
			$( '.footer-text-color, .footer-text-color a' ).css({ 'color': app.storyPageData.footertextcolor });
		} else if ( l10n.story.footertextcolor ) {
			$( '.footer-text-color, .footer-text-color a' ).css({ 'color': '' });
		}
		if ( app.storyPageData.linkcolor ) {
			$( '.story-page a:not( .btn )' ).css({ 'color': app.storyPageData.linkcolor });
		} else if ( l10n.story.linkcolor ) {
			$( '.story-page a:not( .btn )' ).css({ 'color': '' });
		}
		if ( app.storyPageData.arrowcolor ) {
			$( '.arrow-color' ).css({ 'color': app.storyPageData.arrowcolor });
		} else if ( l10n.story.arrowcolor ) {
			$( '.arrow-color' ).css({ 'color': '' });
		}
		if ( app.storyPageData.footerbuttontextcolor ) {
			$( '.footer-button-text-color' ).css({ 'color': app.storyPageData.footerbuttontextcolor });
		} else if ( l10n.story.footerbuttontextcolor ) {
			$( '.footer-button-text-color' ).css({ 'color': '' });
		}

	};

	app.storyPageChange = function( evt ) {

		cache$( '.shifty' ).removeClass( 'show-menu' );
		app.$storyPages.removeClass( 'active' );

		if ( false === app.initPage( evt.newPoint /*customized flipsnap event to provide this*/ ) ) {
			return;
		}

		if ( 'story-page-redirect' === app.storyPage.id ) {
			window.location.href = app.$storyPage.data( 'redirect' );
			return;
		}

		window.location.hash = app.index_names[ app.index ];
		// window.location.hash = app.index + '-' + app.storyPage.id;

		app.triggerVideo();
		app.updateColors();
		app.updateNav();
		app.editLink( app.storyPageData.id );
		app.footerOverrides();
		app.colorOverrides();
	};

	app.initPage = function( index ) {
		app.index = index;
		app.storyPage = app.$storyPages[ app.index ];
		if ( ! app.storyPage ) {
			return false;
		}

		app.$storyPage    = $( app.storyPage ).addClass( 'active' );
		app.video         = app.$storyPage.find( 'video' )[0];  // current video element
		app.storyPageData = app.$storyPage.data( 'storymeta' );

	};

	app.init = function() {
		log( l10n );

		app.$storyPages = cache$( '.story-page' ); // array of story-page elements

		FastClick.attach( document.body );

		app.flipsnap.element.addEventListener( 'fspointmove', app.storyPageChange, false );

		if ( ! app.flipsnap.hasPrev() ) {
			cache$( '.js-story-page-previous' ).addClass( 'hide' );
		}

		if ( window.location.hash ) {

			cache$( '.storybook' ).css({ transition: 'none' });

			window.setTimeout( function() {
				cache$( '.storybook' ).css({ transition: '' });
			}, 500 );

			var ID = window.location.hash.split( '#' )[1];
			app.index = app.names_index[ ID ];
			app.index = isNaN( app.index ) ? 0 : app.index;

			app.flipsnap.moveToPoint( app.index );
		} else {
			app.flipsnap.moveToPoint( 0 );
		}

		$(document).on( 'keydown', app.keydown );

		if ( ! app.is_touch ) {
			app.createVideos();

			// Trigger video for first and subsequent pages
			if ( false !== app.initPage( 0 ) ) {
				app.triggerVideo();
			}
		}

		cache$( 'body' )
			.on( 'click', '.js-story-page-btn', function( evt ) {
				evt.preventDefault();
				app.index = $($(this).attr( 'href' )).index( '.story-page' );
				app.flipsnap.moveToPoint( app.index );
			})
			.on( 'click', '.js-story-page-next', function() {
				app.closeModals();
				app.flipsnap.toNext();
			})
			.on( 'click', '.js-story-page-previous', function() {
				app.closeModals();
				app.flipsnap.toPrev();
			})
			.on( 'click', '.js-shifty-toggle', function() {
				app.toggleMenu();
			})
			.on( 'click', '.js-show-modal', function() {
				var $this      = $(this);
				var vidid      = $this.data( 'vidid' );
				if ( ! vidid ) {
					return;
				}

				var $modal     = cache$( '#'+ vidid ).show();
				var $container = cache$( '.js-embed-wrap', $modal );
				var vidSrc     = $modal.data( 'src' );

				if ( vidSrc ) {

					$container
						.html( $( '<iframe>', {
							src: vidSrc,
							frameborder: 0
						} ) )
						.fitVids();

				} else {
					$container.html( cache$( '.tmpl-videoModal', $modal ).html() ).fitVids();
				}
			})
			.on( 'click', '.js-hide-modal', function() {
				app.closeModals();
			});

	};

	$(document).ready( app.init );

	return app;

})(window, document, jQuery);
