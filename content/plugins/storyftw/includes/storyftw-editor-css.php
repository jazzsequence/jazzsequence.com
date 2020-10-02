<?php
header( 'Content-type: text/css; charset: UTF-8' );

if ( ! isset( $_REQUEST['css'] ) ) {
	return;
}
$css = json_decode( urldecode( $_REQUEST['css'] ) );
$dashicons_url = $_REQUEST['wp_includes_rel_path'] .'css/dashicons.css?ver='. $_REQUEST['version'];
?>
/*
css: <?php print_r( $css ); ?>

version: <?php print_r( $_REQUEST['version'] ); ?>

wp-includes relative path: <?php print_r( $_REQUEST['wp_includes_rel_path'] ); ?>

*/

@import url( '<?php echo $dashicons_url; ?>' );

<?php
foreach ( $css as $selectors => $info ) :
// $selectors = 'html' !== $selectors
// 	// add 'html' to the front of every selector (for more specificity)
// 	? 'html '. implode( ', html ', explode( ',', $selectors ) )
// 	: $selectors;
echo "$selectors {\n";
foreach ( $info as $name => $value ) {
	echo "\t$name: $value;\n";
}
echo "}\n\n";

endforeach;
