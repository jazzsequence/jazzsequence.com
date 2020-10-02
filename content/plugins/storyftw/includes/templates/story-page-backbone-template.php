<!-- <th scope="row" class="check-column">
	<label class="screen-reader-text" for="cb-select-{{{ data.ID }}}"><?php printf( __( 'Select %s', 'storyftw' ), '{{{ data.title }}}' ); ?></label>
	<input id="cb-select-{{{ data.ID }}}" type="checkbox" name="post[]" value="{{{ data.ID }}}">
	<div class="locked-indicator"></div>
</th> -->
<td class="page_order column-page_order">{{{ data.menu_order + 1 }}}</td>
<td class="post-title page-title column-title">

	<strong><a class="row-title" href="{{{ data.edit_link }}}" title="<?php printf( __( 'Edit &#8220;%s&#8221;', 'storyftw' ), '{{{ data.title }}}' ); ?>">{{{ data.title }}}</a></strong>

	<div class="row-actions">

		<span class="edit"><a href="{{{ data.edit_link }}}" title="<?php _e( 'Edit this item', 'storyftw' ); ?>"><?php _e( 'Edit', 'storyftw' ); ?></a> | </span>

		<# if ( 'trash' === data.status ) { #>
		<span class="untrash"><a title="<?php _e( 'Restore this item from the Trash', 'storyftw' ); ?>" href="{{{ data.untrash_link }}}"><?php _e( 'Restore', 'storyftw' ); ?></a> | </span>
		<# } #>

		<span class="trash"><a class="submitdelete" title="{{{ data[ data.toggle + '_helper_text' ] }}}" href="{{{ data[ data.toggle + '_link' ] }}}">{{{ data[ data.toggle + '_label' ] }}}</a> | </span>

		<span class="view"><a href="{{{ data.permalink }}}" title="<?php echo StoryFTW::start()->cpts->story_pages->get_arg( 'labels' )->view_item; ?>"><?php _e( 'View', 'storyftw' ); ?></a></span>

	</div>
</td>
<td class="story_excerpt column-story_excerpt"><p>{{{ data.excerpt }}}</p>
</td>
<td class="date column-date">
	{{{ data.status }}}
	<span class="spinner"></span>
</td>
