<?php
/*
Plugin Name: Fix Rss Feeds
Plugin URI: http://www.gofunnow.com/wordpress/plugins/fix-rss-feed-error-wordpress-plugins.htm
Description: fix wordpress rss feed error "Error on line 2: The processing instruction target matching "[xX][mM][lL]" is not allowed." while you burn wordpress rss feed from http://www.feedburner.com, also fix error "XML or text declaration not at start of entity" in firefox, and fix error "XML declaration not at beginning of document" in opera.
Author: flyaga li
Version: 3.1
date:2011-08-10
Author URI: http://www.gofunnow.com/
Change log:
2008-12-30 release v1.0
2009-02-04 release v1.01, fixed some errors, add create backup files before change php files, thanks for Willem Kossen's advice.
2009-02-16 release v1.02, fixed some errors
2009-05-24 release v1.03, add "check wordpress rss feed error" button, thanks for Wanda's advice.
2010-02-12 release v2.0, add backup and restore function.
2010-09-19 release v3.0, need not to modify error in php files directly, it will use a good and simple way, it just modify wp-blog-header.php in blog directory.
2011-08-10 release v3.1, only open Donate url in first usage time, thanks for Colin Reynolds's advice.
*/
$version='V3.1';
$phpfilename='wp-blog-header.php';
$fixphpfilename=ABSPATH.$phpfilename;
$newphpfilename=dirname(__FILE__).'/'.$phpfilename;
$oldphpfilename=dirname(__FILE__).'/bak/'.$phpfilename;

load_plugin_textdomain('fixrssfeed', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)));

/*option menu*/
if(!function_exists("fixrssfeed_reg_admin")) {
	/**
	* Add the options page in the admin menu
	*/
	function fixrssfeed_reg_admin() {
		if (function_exists('add_options_page')) {
			add_options_page('Fix Rss Feed', 'Fix Rss Feed',8, basename(__FILE__), 'fixrssfeedOption');
			//add_options_page($page_title, $menu_title, $access_level, $file).
		}
	}
}


add_action('admin_menu', 'fixrssfeed_reg_admin');

if(!function_exists("fixrssfeedOption")) {
  function fixrssfeedOption(){
    global $fixphpfilename,$newphpfilename,$version;
    do_fixrssfeed_action();
?>
	<div class="wrap" style="padding:10px 0 0 10px;text-align:left">
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    <p><h2><?php _e("Fix Rss Feed","fixrssfeed"); echo ' '.$version;?></h2></p>
	<p><h3><?php _e("Click the button bellow to fix wordpress rss feed error","fixrssfeed");?></h3></p>
	<p><?php _e('It will fix wordpress rss feed error "<a href="http://www.gofunnow.com/wordpress/fix-wordpress-rss-feed-error.htm" target=_blank>Error on line 2: The processing instruction target matching "[xX][mM][lL]" is not allowed.</a>" while you burn rss feed from http://www.feedburner.com, also fix error "<a href="http://www.gofunnow.com/wordpress/fix-wordpress-rss-feed-error.htm" target=_blank>XML or text declaration not at start of entity</a>" in firefox, and fix error "<a href="http://www.gofunnow.com/wordpress/fix-wordpress-rss-feed-error.htm" target=_blank>XML declaration not at beginning of document</a>" in opera</a>.');?></p>
<?
//2011-08-10 only open Donate url in first usage time, thanks for Colin Reynolds's advice.
  $iIsCheckDonate=(filesize($fixphpfilename)!=filesize($newphpfilename));
?>
  <p><b>Please select a way to support my fix rss feed plugin, help me to  
  continue support and development of this free software, thanks!</b></p> 
  <blockquote>
  <p><input type="radio" value="order" name="supportaction" id="supportaction" <? if($iIsCheckDonate) echo "checked";?> >Donate $4.99&nbsp;   
  using palpay <a href="http://www.gofunnow.com/go/fix-rss-feed-donations1.php" target="_blank"><img border="0" src="http://www.gofunnow.com/image/donate.gif" width="62" height="31"></a><br>
  <input type="radio" value="none" name="supportaction" id="supportaction" <? if(!$iIsCheckDonate) echo "checked";?>  >None</p>
  </blockquote>
    <p><font color="#FF0000"><b>Before fix feed error, you must to set writable permission to <?php echo $fixphpfilename; ?> file, otherwise will no success!</b></font><br>
	<b>You can click below button to fix rss feed error.</b><br><input type="submit" value="<?php _e("Fix wordpress rss feed error","fixrssfeed");?>" id="fixrssfeedDelbt" name="fixrssfeedDelbt" onClick="return fixrssfeedinput(1); " /></p>
</p><b>If there are any errors after fixed rss feed, you can click "restore fix" button to restore</b><br>
<input type="submit" value="<?php _e("Restore Fix","fixrssfeed");?>" id="restorefix" name="restorefix" onClick="return restorefix();"></p>
	</form>
  <br><h3>Thanks for using this plugin!</h3>
  <p>If you are satisfied with the results, isn't it worth at least $4.99? <a href="http://www.gofunnow.com/go/fix-rss-feed-donations1.php" target="_blank"><img border="0" src="http://www.gofunnow.com/image/donate.gif" width="62" height="31"></a> 
  help me to continue support and development of this free software!</p> 
  <h3>Informations and support</h3>
  <p>Check <a href="http://www.gofunnow.com/wordpress/plugins/fix-rss-feed-error-wordpress-plugins.htm" target="_blank">http://www.gofunnow.com/wordpress/plugins/fix-rss-feed-error-wordpress-plugins.htm</a> 
  for updates and comment there if you have any problems / questions / 
  suggestions.</p>
	</div>

	<SCRIPT LANGUAGE="JavaScript">
	<!--
	function GetRadioValue(RadioName){
		var obj;   
	    obj=document.getElementsByName(RadioName);
		if(obj!=null){
			var i;
	        for(i=0;i<obj.length;i++){
		        if(obj[i].checked){
			        return obj[i].value;           
				}
	        }
		}
	    return null;
	}

	function fixrssfeedinput(isfix)
    {
		if(isfix)
			document.getElementById('fixrssfeedDelbt').value ='<?php _e("Please Wait...","fixrssfeed");?>';

        if(GetRadioValue("supportaction")=='order')
          window.open('http://www.gofunnow.com/go/fix-rss-feed-donations1.php','');
		return true;
	}

	function restorefix()
	{
		document.getElementById('restorefix').value ='<?php _e("Please Wait, restoring ...","fixrssfeed");?>';
		return true;
	}
	//-->
	</SCRIPT>
<?php
	}
}

if(!function_exists("debugwrite")) {

function debugwrite($text)
{
  echo $text."<br>";
}

function debugwriteend($text)
{
  die($text."<br>");
}

function errorwrite($text,$color="#FF0000")
{
  debugwrite("<font color='$color'>".$text."</font>");
}

function errorwriteend($text,$color="#FF0000")
{
  die("<font color='$color'>".$text."</font><br>");
}
}

function do_fixrssfeed_action(){
  global $fixphpfilename,$newphpfilename,$oldphpfilename;

  if(!empty($_POST['restorefix'])){
    if(!file_exists($oldphpfilename))
    {
      errorwrite(_("Error, $oldphpfilename is not existed, please contact support@gofunnow.com to get help!","fixrssfeed"));
    }

    if(copy($oldphpfilename, $fixphpfilename))
    {
      $msg="Restore fix ok!";
      echo '<div class="updated"><strong><p>'.$msg.'</p></strong></div>';
    }
    else
    {
      errorwrite(_("Restore fix failure, please contact support@gofunnow.com to get help!","fixrssfeed"));
    }

    return;
  }

  if(empty($_POST['fixrssfeedDelbt']))
    return;

  //create backup files before change php files
  if(!file_exists($oldphpfilename))
  {
    errorwrite(_("Error, $oldphpfilename is not existed, please contact support@gofunnow.com to get help!","fixrssfeed"));
  }

  if (!copy($fixphpfilename, $oldphpfilename))
  {
    errorwrite($oldphpfilename.__(" can not be overwrited, please check file permission","fixrssfeed"));
  }

  if(!file_exists($fixphpfilename))
  {
    errorwrite(_("Error, $fixphpfilename is not existed, please contact support@gofunnow.com to get help!","fixrssfeed"));
    return;
  }

  if (!is_writable($fixphpfilename)) {
    errorwrite(_("Error, $fixphpfilename can not be writed, you must set writable permission to $fixphpfilename file, otherwise will no success!","fixrssfeed"));
      return;
  }

  if(!file_exists($newphpfilename))
  {
    errorwrite(_("Error, $newphpfilename is not existed, you must get all plugins files, otherwise will no success!","fixrssfeed"));
      return;
  }

  if(copy($newphpfilename, $fixphpfilename))
  {
    $rssurl=get_bloginfo('rss2_url');
    $msg="Success, your rss feed had been fixed, you can check it from <a href='$rssurl' target='_blank'>$rssurl</a>";
  }
  else
  {
    errorwrite(_("Error, $fixphpfilename can not be writed, you must set writable permission to $fixphpfilename file, otherwise will no success!","fixrssfeed"));
    return;
  }

  if($msg)
	echo '<div class="updated"><strong><p>'.$msg.'</p></strong></div>';
}
?>