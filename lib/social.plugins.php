<?php
    if( !defined(APP_URL) ) 
		define( 'APP_URL', 'http://apps.facebook.com/php_demo' );
		
	// for use in the website, not the facebook app canvas
	// these functions can be inserted to get FB functionality in the site
	// this include needs to be moved out of the FB only include and into 
	// the website pages to use FB socail plugins on both the app and the site
	
	// div goes (ideally) at BODY top for loading the FB api async
	// using fbs_async_load at BODY end (inside of body tag)
	// you will have to add this to your standard website templates to 
	// integrate facebook into your site. this is the div where facebook
	// APIs/SDK are loaded
	function fbs_root_div() {
		?>
		<!-- JS SDK docs: http://github.com/facebook/connect-js -->
		<div id="fb-root"></div>
		<?php
	}
	
	// add inside of BODY tag at the end of the page to async
	// load the facebook JS SDK asynchronously
	// you will have to add this to your standard website templates to 
	// integrate facebook into your site. 
	function fbs_async_load() {
		// echo __FUNCTION__ . '<br/>';
		// $cookie = FB_lib::get_facebook_cookie();
		// echo 'fbs_async_load cookie: '; mpuke( $cookie );
		?>
		<script>
		  var fbApiInitialized = false;
		  // var cookie = <?php echo json_encode( $cookie ); ?>;
		  window.fbAsyncInit = function() {
		    FB.init({
		      appId  	: '<?php echo APP_ID; ?>',
			  <?php/*  if( $cookie['session_key'] && $cookie['access_token'] ): ?>
			    session 	: <?php echo json_encode( $cookie ); ?>,
		  	  <?php  endif; */?>
		      status 	: true, 
		      cookie 	: true, 
		      xfbml  	: true
		    });
		
			fbApiInitialized = true;
			FB.getLoginStatus(function(response) {

			  if (response.session) {
				
				// how you make api calls with the JS api
				/*
				FB.api('/me', function(response) {
				  alert(response.name);
				});
				*/
				// is_app_user
				/*
				FB.api(
				  {
				    method	: 'fql.query',
				    query	: 'SELECT is_app_user FROM user WHERE uid=' + cookie.uid
				  },
				  function(response) {
				    // $("#fql-test").html( JSON.stringify( response ) ); // 'Name is ' + response[0].name );
					alert( JSON.stringify( response ) );
				  }
				);
				*/
				
			    // user successfully logged in
				// add code here to do stuff at this point if you want

			  } else {
			    // user cancelled login
				// add code here to do stuff at this point if you want
				
			  }
			});
			
			initComplete(); // handle any post FB api loading stuff
			
		  }; // fbAsyncInit

		  (function() {
		    var e = document.createElement('script');
		    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
		    e.async = true;
		    document.getElementById('fb-root').appendChild(e);
		  }());
		
		
		function initComplete(){
			// auto-resize the canvas to avoid scrollbars in the app window frame
			// two scrollbars on a page looks stupid.
			FB.XFBML.parse();
			FB.Canvas.setAutoResize();
			
			// var root = document.getElementById('jsroot');
			// FB.XFBML.parse(root);
			// FB.XFBML.Host.parseDomTree(root);
		}
		
		function tab_added(){
			top.location.href = '<?php echo APP_URL; ?>/';
		}
		</script>
		<?php
	}
	
	// this stuff is outlined here: http://developers.facebook.com/docs/opengraph
	// the auth system will have to be updated to use oAuth rather than the old
	// method. This will include turning on oAuth in the Facebook App admin
	// console. until that happens you won't have a complete enough session
	// to act on behalf of users at the level you need.
	
	// should be FB meta tags that are single product-view specific
	// TO DO - add code to complete meta tag values as appropriate
	function fbs_product_meta( ) {
		global $_PAGE; global $_RESOLVED;
		$store = $_PAGE["store"];
	    $product = cosmo_cached("product", $_PAGE["featured_product"]);
		// title_uri
		?>
		<meta property="fb:app_id" content="<? echo APP_ID; ?>" />
		<meta property="og:title" content="Demo Php Facebook App"/>
		<meta property="og:site_name" content="michaelreed.org"/>
	    <!--meta property="og:type" content=""/-->
	    <meta property="og:url" 
			content="http://michaelreed.org/" 
			/>
		<?
		// TO DO, add the featured product image URL to the below tag to tell FB likes
		// that are posted to a users wall to pull the correct image
		?>
	    <meta property="og:image" content=""/>
		<meta property="og:description"
		          content=""/>
		<?
	}
	
	// should be FB meta tags that are whole shop-view specific
	// TO DO - add code to complete meta tag values as appropriate
	function fbs_shop_meta( ) {
		global $_PAGE;
		?>
		<meta property="fb:app_id" content="<? echo APP_ID; ?>" />
		<meta property="og:title" content="Demo Php Facebook App"/>
		<meta property="og:site_name" content="michaelreed.org"/>
	    <!--meta property="og:type" content=""/-->
	    <meta property="og:url" 
			content="http://michaelreed.org/" 
			/>
	    <meta property="og:image" content=""/>
		<meta property="og:description"
		          content=""/>
		<?
	}
	
	// should be FB meta tags that are non-product and non-whoe-shop
	// TO DO - add code to complete meta tag values as appropriate
	function fbs_page_meta( ) {
		global $_RESOLVED;
		?>
		<meta property="fb:app_id" content="<? echo APP_ID; ?>" />
		<meta property="og:title" content="Demo Php Facebook App"/>
		<meta property="og:site_name" content="michaelreed.org"/>
		<meta property="og:type" content=""/>
	    <meta property="og:url" content=""/>
	    <meta property="og:image" content=""/>
		<?
	}
	
	// FB REF: http://developers.facebook.com/docs/reference/plugins/like
	function fbs_like_button( $uri="" ){
		global $_RESOLVED;
		if( ""==$uri )
			$uri = "http://michaelreed.org/";
		?>
		<fb:like href="<?php echo $uri; ?>" width="300" action="like" layout="button_count"
			show_faces="false" font="lucida grande" colorscheme="light"
		></fb:like>
		<?php
	}
	
	function fbml_google_analytics(){
		?>
		<fb:google-analytics uacct="UA-769343-1" ucmd="facebook" ucsr="facebook.com"
			ucsr="Facebook App" 
			></fb:google-analytics>
		<?
	}
	
?>