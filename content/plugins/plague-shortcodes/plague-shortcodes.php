<?php
/*Plugin Name: Plague Shortcodes
Plugin URI: http://museumthemes.com
Description: Shortcodes for the plague site
Version: 1.0
Author: Chris Reynolds
Author URI: http://chrisreynolds.io/
License: GPLv3
License URI: http://gnu.org/licenses/gpl.html
*/

add_shortcode('plague_artists', 'plague_artist_list');
function plague_artist_list($atts = array(), $content = null, $tag = null){
	ob_start();
	?><div class="row"><?php
	$myquery = new WP_Query('post_type=plague-artist&orderby=rand');
	if ($myquery->have_posts()) : while ($myquery->have_posts()) : $myquery->the_post(); ?>
 	<div <?php post_class('col-md-4 col-sm-6'); ?>" id="post-<?php the_ID(); ?>">
		<div class="entry artist">
			<div class="thumbnail">
				<?php if ( has_post_thumbnail() ) { ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( array( 'thumbnail', 'class' => ' aligncenter' ) ); ?></a><?php } ?>
				<div class="caption">
					<h3 class="the_title"><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h3>
				</div>
			</div>
		</div>
    </div>
        <?php endwhile; endif; wp_reset_query();
    ?></div><?php
    return ob_get_clean();
}