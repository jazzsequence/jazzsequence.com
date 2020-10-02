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

/*global _:false */
/*global wp:false */

( function( window, document, $, app, undefined ) {

	'use strict';

	var l10n   = window.StoryFTW_l10n;
	var bb     = window.Backbone;
	var log    = window.StoryFTW.Utils.log;
	var cache$ = window.StoryFTW.Utils.cache;
	app.Views = {};

	/**
	 * Model
	 */
	app.Page = bb.Model.extend({
		defaults: {
			'ID'          : null,
			'menu_order'  : null,
			'title'       : null,
			'permalink'   : null,
			'edit_link'   : null,
			'delete_link' : null,
			'status'      : null,
			'excerpt'     : null
		}
	});

	/**
	 * Collection
	 */
	app.Pages = bb.Collection.extend({
		model           : app.Page,
		sort_key        : 'menu_order',
		sort_menu_order : 'ASC',
		sort_title      : 'ASC',
		sort_status     : 'ASC',

		getbyID: function( pageID ) {
			pageID = parseInt( pageID, 10 );
			return this.find( function( model ) {
				return pageID === parseInt( model.get( 'ID' ), 10 );
			} );
		},

		comparator: function( a, b ) {
			a = a.get( this.sort_key );
			b = b.get( this.sort_key );

			if ( 'ASC' === this[ 'sort_'+ this.sort_key ] ) {
				return a > b ? 1
					: a < b ? -1
					: 0;
			} else {
				return a < b ? 1
					: a > b ? -1
					: 0;
			}
		},

		search : function( letters ){
			if ( ! letters ) {
				return this;
			}

			var pattern = new RegExp( letters, 'gi' );
			return _( this.filter( function( model ) {
				return pattern.test( model.get( 'title' ) ) || pattern.test( model.get( 'excerpt' ) );
			} ) );
		}

	});

	/**
	 * Table View
	 */
	app.Views.Table = bb.View.extend({
		events : {
			'click [data-sortby]'         : 'sortBy',
			'onsearch #post-search-input' : 'render',
			'search #post-search-input'   : 'render',
			'keyup #post-search-input'    : 'search'
		},

		spinner : '',

		initialize: function() {
			this.listenTo( this.collection, 'change', this.changeHandler );
			this.listenTo( this.collection, 'render reset sort', this.render );

			this.$list    = this.$el.find( '#the-list' );
			this.$search  = this.$( '#post-search-input' );

			// var pageOrder = this.collection.invoke( 'get', 'ID' );
			cache$( '#major-publishing-actions' ).append( '<input type="hidden" id="story-page-order" name="story-page-order" value="" />' );


			this.render();

			var thisView = this;

			cache$( '#the-list' ).disableSelection().sortable({
				items       : '> tr',
				cursor      : 'move',
				axis        : 'y',
				containment : 'table.widefat',
				cancel      :	'.inline-edit-row',
				distance    : 2,
				opacity     : 0.8,
				tolerance   : 'pointer',
				start       : this.startDrag,
				helper      : this.dragHelper,
				stop        : this.stopDrag,
				update      : function() {
					thisView.updateAfterDrag( thisView );
				}
			});

		},

		search: function( evt ) {
			if ( evt.which === 13 || evt.which === 27 ) {
				this.$search.val('');
			} else {
				this.renderItems( this.collection.search( this.$search.val() ) );
			}
		},

		sortBy: function( evt ) {
			log( 'click', evt );
			evt.preventDefault();

			var $this = $( evt.currentTarget );
			this.collection.sort_key = $this.data( 'sortby' );

			if ( this.collection[ 'sort_'+ this.collection.sort_key ] === 'ASC' ) {

				$this.removeClass( 'desc' ).addClass( 'asc' );
				this.collection[ 'sort_'+ this.collection.sort_key ] = 'DESC';
			} else {
				$this.removeClass( 'asc' ).addClass( 'desc' );
				this.collection[ 'sort_'+ this.collection.sort_key ] = 'ASC';
			}
			this.collection.sort();

		},

		changeHandler: function( modelChanged ) {
			log( 'modelChanged', modelChanged.changed );
			modelChanged.trigger( 'modelChanged' );
		},

		startDrag: function( evt, ui ){
			ui.placeholder.height( ui.item.height() );
		},

		dragHelper: function( evt, ui ) {
			var children = ui.children();
			for ( var i = 0; i < children.length; i++ ) {
				var selector = $( children[ i ] );
				selector.width( selector.width() );
			}

			return ui;
		},

		stopDrag: function( evt, ui ) {
			// remove fixed widths
			ui.item.children().css( 'width', '' );
		},

		updateAfterDrag: function( thisView ) {

			var pageOrder = [];
			var collection = thisView.collection;

			cache$( '#the-list' ).find( 'tr.iedit' ).each( function( index ) {

				var pageID = $( this ).data( 'pageid' );

				pageOrder.push( pageID );

				var model = collection.getbyID( pageID );

				model.set( 'menu_order', index );
			});

			cache$( '#story-page-order' ).val( pageOrder.join( ',' ) );
		},

		render: function() {
			this.renderItems( this.collection );
		},

		renderItems: function( toRender ) {

			var addedElements = document.createDocumentFragment();

			var count;
			// render each row, appending to our root element
			toRender.each( function( model, index ) {
				count = index;

				var row = new app.Views.Row({ model: model });
				addedElements.appendChild( row.render().el );
			});

			log( 'countcount', count );

			count = isNaN( count ) ? 0 : parseInt( count + 1, 10 );
			cache$( '.displaying-num span' ).text( count );

			this.$list.html( addedElements );

			this.updateAfterDrag( this );
		}
	});

	/**
	 * Single row
	 */
	app.Views.Row = bb.View.extend({
		tagName: 'tr',

		events: {
			'click .submitdelete' : 'submitDelete',
			'click .untrash a' : 'submitRestore'
		},

		template: wp.template( 'rowTemplate' ),

		id : function() {
			return 'post-'+ this.model.get( 'ID' );
		},

		className : function() {

			// log( 'className', this.model.get( 'menu_order' ) );

			var altClass = 0 === this.model.get( 'menu_order' ) % 2 ? 'alternate' : '';

			return 'post-'+ this.model.get( 'ID' ) +' type-storyftw_story_pages status-publish hentry '+ altClass +' iedit author-self level-0 status-'+ this.model.get( 'status' );
		},

		attributes: function() {
			return {
				'data-pageid' : this.model.get( 'ID' )
			};
		},

		initialize : function() {
			this.restoreStatus = this.model.get( 'status' );

			this.listenTo( this.model, 'modelChanged', this.render );
			this.listenTo( this.model, 'render', this.render );
		},

		// Render the row
		render: function() {
			// log( 'render row' );

			var classes = 'function' === typeof this.className ? this.className() : this.className;

			var data = this.model.toJSON();
			data.toggle = 'trash' === data.status ? 'delete' : 'trash';

			this.$el.html( this.template( data ) ).attr( 'class', classes );

			return this;
		},

		submitDelete: function( evt ) {
			evt.preventDefault();

			var self = this;
			var url = $( evt.currentTarget ).attr( 'href' );

			this.asyncSubmit( url, function( $msg ) {
				if ( $msg.hasClass( 'updated' ) ) {
					if ( url.indexOf( 'action=delete' ) > -1 ) {
						self.removeModel();
					} else {
						self.model.set( 'status', 'trash' );
					}
				}
			});
		},

		submitRestore: function( evt ) {
			evt.preventDefault();

			var self = this;

			this.asyncSubmit( $( evt.currentTarget ).attr( 'href' ), function( $msg ) {
				if ( $msg.hasClass( 'updated' ) ) {
					self.model.set( 'status', self.restoreStatus );
				}
			});
		},

		asyncSubmit: function( url, cb ) {
			var self = this;
			self.$( '.spinner' ).show();
			$.ajax({
				url      : url,
				type     : 'GET',
				dataType : 'html',
				cache    : false
			}).done( function( response ) {
				self.$( '.spinner' ).hide();

				var $html  = $("<div>").append( $.parseHTML( response, document, true ) );
				var $msg = $html.find( '#wpbody-content .wrap #message' );

				log( 'text', $msg.text() );
				log( 'class', $msg.attr( 'class' ) );

				if ( 'function' === typeof cb ) {
					cb( $msg );
				}
			});
		},

		removeModel: function() {
			var self = this;
			var collection = self.model.collection;
			self.model.destroy({ success: function() {
				self.$el.fadeOut( 300, function() {
					self.$el.remove();
					collection.trigger( 'render' );
				} );
			}});
		}

	});

	app.overridePostSearchInput = function() {
		setTimeout( function() {
			if ( ! window.cmb2_post_search ) {
				return;
			}
			window.cmb2_post_search.handleSelected = function( checked ) {
				var self      = this;
				var permalink = this.$checked.length ? this.$checked.last().data( 'permalink' ) : false;

				if ( permalink ) {
					self.$idInput.val( permalink );
					self.close();
					return;
				}

				$.ajax( window.ajaxurl, {
					type     : 'POST',
					dataType : 'json',
					data     : {
						action: 'storyftw_get_permalink',
						post_id: checked
					}
				}).done( function( response ) {

					log( 'response', response );

					if ( response.success ) {
						self.$idInput.val( response.data );
					}
					self.close();
				}).fail( function() {
					self.close();
				});

			};

		}, 200 );
	};

	app.resetPickerChangeCB = function() {
		var picker = cache$( '#_storyftw_fallback_bg_color' ).data( 'wpWpColorPicker' );
		if ( ! picker.options ) {
			return;
		}

		picker.options.change = function( evt, ui ) {
			var color = ui.color.toString().trim();
			var exists = false;
			var $noColor = {};
			cache$( '.cmb2-id--storyftw-palettes' ).find( '#_storyftw_palettes_repeat input[type="text"].cmb2-colorpicker' ).each( function() {
				var thisColor = $(this).iris( 'color' );
				if ( color === thisColor ) {
					exists = true;
				}
				if ( ! thisColor && ! $noColor.length ) {
					$noColor = $(this);
				}
			});

			if ( exists || ! $noColor.length ) {
				return;
			}

			$noColor.iris( 'color', color ).val( color );
			cache$( '.cmb-add-row-button', cache$( '.cmb2-id--storyftw-palettes' ) ).trigger( 'click' );
		};
	};

	app.init = function() {
		log( l10n );
		log( cache$ );

		log( 'l10n.pages', l10n.pages );
		var pages = _.toArray( l10n.pages );

		// Uh-oh, something's wrong
		if ( pages.length ) {
			// Send the model data to our builder view
			app.collection = new app.Views.Table({
				collection: new app.Pages( pages ),
				el: '.story-pages-wrap'
			});
		}

		// wait a bit to be sure shortcode button js loaded
		setTimeout( app.resetPickerChangeCB, 500 );

		app.advancedToggle();
		app.overridePostSearchInput();
	};

	$(document).ready( app.init );

	return app;

})( window, document, jQuery, window.StoryFTW );
