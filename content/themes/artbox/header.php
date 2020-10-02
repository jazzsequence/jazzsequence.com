<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

	<title><?php bloginfo('name'); ?><?php wp_title(); ?></title>

	<style type="text/css" media="screen">
		@import url( <?php bloginfo('stylesheet_url'); ?> );
	</style>

	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="alternate" type="text/xml" title="RSS .92" href="<?php bloginfo('rss_url'); ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom 1.0" href="<?php bloginfo('atom_url'); ?>" />

	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<?php wp_get_archives('type=monthly&format=link'); ?>
	<?php //comments_popup_script(); // off by default ?>
	<?php wp_head(); ?>
<!--this makes transparent pngs not suck in ie5-6-->
<!--[if lt IE 7.]>
<script defer type="text/javascript" src="pngfix.js"></script>
<![endif]-->
<!--this is calling an alternate stylesheet for ie-->
<!--[if IE]>
<style type="text/css">
#content {
	
   }
</style>
<![endif]-->    
</head>

<body>
<div id="rap">
<a href="<?php bloginfo('url'); ?>" title="<?php bloginfo('name'); ?> | <?php bloginfo('description'); ?>"><div id="header" align="left">
<img src="<?php bloginfo('url'); ?>/wp-content/themes/artbox/images/kidsblogtitle.png"></div></a>

<div id="content">
<!-- end header -->
