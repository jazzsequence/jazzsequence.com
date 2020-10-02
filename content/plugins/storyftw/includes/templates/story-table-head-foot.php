<tr>
	<!-- <th scope="col" <?php if ( $top ) : ?>id="cb"<?php endif; ?> class="manage-column column-cb check-column">
		<label class="screen-reader-text" <?php if ( $top ) : ?>for="cb-select-all-1"<?php endif; ?>><?php _e( 'Select All', 'storyftw' ); ?></label><input <?php if ( $top ) : ?>id="cb-select-all-1"<?php endif; ?> type="checkbox">
	</th> -->
	<th scope="col" <?php if ( $top ) : ?>id="page_order"<?php endif; ?> class="manage-column column-page_order sortable desc" data-sortby="menu_order"><a href="#"><span>#</span><span class="sorting-indicator"></span></a></th>
	<th scope="col" <?php if ( $top ) : ?>id="title"<?php endif; ?> class="manage-column column-title sortable desc" data-sortby="title">
		<a href="#"><span><?php _e( 'Title', 'storyftw' ); ?></span><span class="sorting-indicator"></span></a>
	</th>
	<th scope="col" <?php if ( $top ) : ?>id="story_excerpt"<?php endif; ?> class="manage-column column-story_excerpt"><?php _e( 'Excerpt', 'storyftw' ); ?></th>
	<th scope="col" <?php if ( $top ) : ?>id="date"<?php endif; ?> class="manage-column column-date sortable asc" data-sortby="status">
		<a href="#"><span><?php _e( 'Status', 'storyftw' ); ?></span><span class="sorting-indicator"></span></a>
	</th>
</tr>
