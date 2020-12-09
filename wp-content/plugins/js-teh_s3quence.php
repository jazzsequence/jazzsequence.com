<?php 
/*
Plugin Name: jazzsequence teh s3quence
Description: this plugin takes care of the teh s3quence page template by turning the frakker into a shortcode.  boo yah beyotch.
Version: 0.1
Author: jazzs3quence
Author URI: http://jazzsequence.com/
*/

function teh_s3quence($atts, $content = null) {

    $html = '<em>teh s3quence</em> is just a name i started attaching to mixtapes (and i use that term very loosely since there\'s no tape involved at all) i started recording and posting here.  each set is different (and i post the tracklist).  not just different meaning that it\'s not the same as the last, i mean different in that the genre, style, and mood is different, because i like a lot of different stuff.  check back often because this page will update when i post new sets.
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
$query .= '&tag=teh-s3quence';
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
add_shortcode("tehs3", "teh_s3quence");


?>