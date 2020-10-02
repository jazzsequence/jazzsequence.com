<?php $labels = StoryFTW::start()->cpts->story_pages->get_arg( 'labels' ); ?>
<div class="story-pages-wrap">
	<h2><?php echo $labels->name; ?> <a href="<?php echo esc_url( $this->new_story_page_url() ); ?>" class="add-new-h2" ><?php echo $labels->add_new; ?></a></h2>

	<!-- <ul class="subsubsub">
		<li class="all"><a href="#" class="current"><?php _e( 'All', 'storyftw' ); ?> <span class="count">(#)</span></a> |</li>
		<li class="publish"><a href="#"><?php _e( 'Published', 'storyftw' ); ?> <span class="count">(#)</span></a></li>
	</ul> -->

	<p class="search-box">
		<label class="screen-reader-text" for="post-search-input"></label>
		<input type="search" id="post-search-input" value="" placeholder="<?php _e( 'Type to filter Story Pages', 'storyftw' ); ?>">
	</p>
	<div class="tablenav top">

		<?php $this->story_pages_listing_tablenav(); ?>
	</div>
	<table class="wp-list-table widefat fixed pages">

		<thead>
			<?php $this->story_pages_listing_head_foot(); ?>
		</thead>

		<tfoot>
			<?php $this->story_pages_listing_head_foot( false ); ?>
		</tfoot>

		<tbody id="the-list">
		</tbody>

	</table>

	<div class="tablenav bottom">

		<?php $this->story_pages_listing_tablenav( false ); ?>
	</div>

	<br class="clear">
</div>
