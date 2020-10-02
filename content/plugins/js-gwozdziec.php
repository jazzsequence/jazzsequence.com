<?php 
/*
Plugin Name: jazzsequence gwozdziec
Description: this plugin takes care of the gwozdziec page template by turning the frakker into a shortcode.  boo yah beyotch.
Version: 0.1
Author: jazzs3quence
Author URI: http://jazzsequence.com/
*/


function gwozdziec($atts, $content = null) {

    $html = '<div class="alignleft"><img src="http://www.jazzsequence.com/wp-content/uploads/2009/02/randomalbumcover-300x279.png" /></div><em>gwozdziec</em> is a project i started working on after one of those facebook memes came around, which happened to coincide with when i was just finishing up my <a href="http://www.jazzsequence.com/music/s3quence/">rpm09 project</a>.  the basic premise of the meme was to create a fake album using randomized methods to obtain album art, artist name, and album name, and the challenge was to combine all 3 to create a fake album cover.  but, while fun, this didn\'t seem to me to take the concept to the full extent and logical conclusion, which is: why not make the whole album?  the gwozdziec project is one in which i attempted to use randomized methods for acquiring and/or generating sound files or tracks.  then i mixed the random results into individual compositions (using somewhat less random methods) and posted the entire process here so others could reproduce the same process if they wanted.  the single, common theme of the project was to only use materials released under a creative commons liscense for everything, and release each track in kind.  all the compositions in the gwozdziec project are composed entirely of CC-based work -- there was no original live performance or synthesized sounds that came as part of any pack or software -- every "instrument" is listed, accounted for, and credited to the original source.
        <div class="spacer-10"></div>
        <div class="spacer-10"></div>';
extract(shortcode_atts(array(
"pagination" => 'true',
"query" => '',
"category" => '',
), $atts));
global $wp_query,$paged,$post;
$temp = $wp_query;
$wp_query= null;
$wp_query = new WP_Query();
$query .= '&tag=gwozdziec';
$wp_query->query($query);
ob_start();
?>
<?php while ($wp_query->have_posts()) : $wp_query->the_post(); ?>
				<div <?php post_class('post'); ?> id="post-<?php the_ID(); ?>">				
				<h2 class="the_title"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
                <div class="clear"></div>
                <div class="the_entry"><div class="alignleft"><a href="<?php the_permalink() ?>" title="<?php the_title_attribute(); ?>"><?php the_post_thumbnail(); ?></a></div>
                <?php the_excerpt(); ?></div>
                <div class="clear"></div>
				</div>
<?php endwhile; ?>
		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
			<div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
		</div>


<?php $wp_query = null; $wp_query = $temp;
$content = ob_get_contents();
ob_end_clean();
return $html . $content;
}
add_shortcode("gwoz", "gwozdziec");


?>