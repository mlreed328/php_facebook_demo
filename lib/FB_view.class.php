<?php

class FB_view{
	
	
	
	
	public static function default_page_tab_view(){
		?>
		DEFAULT PAGE TAB VIEW
		<?php
	}
	
	public static function app_header(){
		?>
		APP HEADER
		<?php
	}
	
	public static function default_app_view(){
		?>
		DEFAULT APP VIEW
		<?php
	}
	
	// page header included on every profile tab view
	// all items on the profile tab must be valid HTML/fbml and certain restrictions do
	// apply - FBML REF: http://developers.facebook.com/docs/reference/fbml/
	// uses social plugins vis javascript SDK (and also on App pages and anything
	// you eventually put on the skreened site itself to provide web integration)
	// JS SDK REF: http://developers.facebook.com/docs/reference/javascript/
	public static function page_tab_header(){
		?>
		<link rel="stylesheet" type="text/css" href="<?=DIRECT_URL?>style.css?v=<?php echo APP_VERSION ?>" />
		<?
	}
	
	// page header included on every App page (not profile page) - which is, after all, just
	// a web page proxied from your server through facebook
	// the JS was grabbed from the Skreened.com header - some of these may not be used now
	// but will be useful later as the functionality is extended
	// I have made copies of the CSS - they required some modifications. I probably could
	// have just overridden some stuff in the simplified.css file
	public static function page_header(){
		?>
		<!doctype html>
		<html xmlns:fb="http://www.facebook.com/2008/fbml">
		<head>
			<title>TITLE</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<!-- can include javascript here -->
			<link rel="stylesheet" type="text/css" href="<?=DIRECT_URL?>style.css?v=<?php echo APP_VERSION ?>" />
		</head>
		<body>
		<?php 
		fbs_root_div(); // in social.plugins.php
	}
	
	// inclded on every app page in the footer. does the work of adding google analytics
	// and finishing the pieces-parts for loading the JS SDK asynchronously
	// the functions called in this function are in the social.plugins.php file
	public static function page_footer(){
			fbs_async_load();  // in social.plugins.php
		?>

			<script type="text/javascript">
				var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
				document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
				</script>
				<script type="text/javascript">
				var pageTracker = _gat._getTracker("UA-769343-1");
				pageTracker._initData();
				pageTracker._trackPageview();
			</script>

			</body>
		</html>
		<?
	}
}

?>