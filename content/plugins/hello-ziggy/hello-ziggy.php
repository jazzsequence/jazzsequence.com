<?php
/*
	Plugin Name: Hello Ziggy
	Description: Instead of "Hello Dolly", this plugin will display a random lyric from David Bowie's "Ziggy Stardust".
	Version: 2.1
	Author: Chris Reynolds
	Author URI: https://jazzsequence.com
	License: GPL3
*/

/*
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.


	http://www.opensource.org/licenses/gpl-3.0.html
*/

function hello_ziggy_get_lyric() {
	/** These are the lyrics to Ziggy Stardust */
	$lyrics = "Ziggy played guitar
	jamming good with weird and gilly
	And the spiders from mars
	he played it left hand
	But made it too far
	Became the special man
	then we were Ziggy's band
	Ziggy really sang
	screwed up eyes and screwed down hairdo
	Like some cat from japan
	He could lick 'em by smiling
	He could leave 'em to hang
	They came on so loaded man
	well hung and snow white tan
	So where were the spiders
	while the fly tried to break our balls
	Just the beer light to guide us
	So we bitched about his fans
	and should we crush his sweet hands?
	Ziggy played for time
	jiving us that we were voodoo
	The kids were just crass
	he was the nazz
	With god given ass
	He took it all too far
	but boy could he play guitar
	Making love with his ego
	Ziggy sucked up into his mind
	Like a leper messiah
	When the kids had killed the man i had to break up the band.
	Oh yeah
	Ooooooo
	Ziggy played guitaarrrrrr";

	// Here we split it into lines.
	$lyrics = explode( "\n", $lyrics );

	// And then randomly choose a line.
	return wptexturize( $lyrics[ mt_rand( 0, count( $lyrics ) - 1 ) ] );
}

// This just echoes the chosen line, we'll position it later.
function hello_ziggy() {
	ob_start();
	?>
	<p id="ziggy">
		<?php echo esc_html( hello_ziggy_get_lyric() ); ?>
	</p>
	<?php
	echo wp_kses_post( ob_get_clean() );
}

// Now we set that function up to execute when the admin_footer action is called.
add_action( 'admin_footer', 'hello_ziggy' );

// We need some CSS to position the paragraph.
function ziggy_css() {
	wp_enqueue_style( 'hello-ziggy', plugin_dir_url( __FILE__ ) . '/assets/css/ziggy.css' );
}

add_action( 'admin_enqueue_scripts', 'ziggy_css' );

?>