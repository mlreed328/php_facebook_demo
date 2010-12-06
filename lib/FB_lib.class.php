<?php
$dr = $_SERVER['DOCUMENT_ROOT'] . "/php_fb_demo";

// new Facebook provided php sdk include 
require_once $dr . '/lib/php-sdk/src/facebook.php';

require_once $dr . '/lib/social.plugins.php';

/* constants */
if (!defined( APP_PAGE ) )
  define( 'APP_PAGE', 'http://www.facebook.com/apps/application.php?id=166276383410958' );

if (!defined( GRAPH_ENDPOINT ) )	define( 'GRAPH_ENDPOINT', 'https://graph.facebook.com/' );
if( !defined(APP_ID) ) 				define( 'APP_ID', '166276383410958' );
if( !defined(FB_API_KEY) ) 			define( 'FB_API_KEY', '044803dab154bf8ae2ac4acdbd5fd8a6' );
if( !defined(FB_SECRET) ) 			define( 'FB_SECRET', '4fc411bb87d3ee5c4de2896622edf05c' );
if( !defined(APP_URL) ) 			define( 'APP_URL', 'http://apps.facebook.com/php_demo' ); 
if( !defined(DIRECT_URL) ) 			define( 'DIRECT_URL', 'http://michaelreed.org/php_fb_demo/' );
if( !defined(AUTH_URL) ) 			define( 'AUTH_URL', 'http://michaelreed.org/php_fb_demo/' );


// APP_VERSION can be updated to force loading of CSS/JS as it is tacked onto the end of 
// these requests in the page header	
if( !defined(APP_VERSION) ) 		define( 'APP_VERSION', '0.3997c' );
	

if( !defined(MSG_PAGE_ERR) ) 
	define( 'MSG_PAGE_ERR', '<p>An error occurred while while loading the page.</p>' );


class FB_lib{
	
	// FB object from the php-sdk 
	public static $facebook;
	
	// pages user administers (in self::$accounts['data'])
	public static $accounts;
	
	// info returned from graph.facebook.com/me
	public static $me;
	
	// graph api call URL/me/friends
	public static $friends;
	
	// various data about the user and their interaction with
	// the app that doesn't fit or belong wedged into the FB->$me object
	public static $user = array(
		'has_added_app' => 0, 					/* lets assume not */
		'granted_permissions' => array() 		/* will be populated with a FQL query */
	);
	
	// Facebook permissions your app is requesting
	// see FB REF: http://developers.facebook.com/docs/authentication/permissions
	public static $perms_req = 'email,read_stream,publish_stream';
	
	// oAuth token holders
	public static $oauth_token;
	public static $access_token;
	
	public static $session;
	
	// the FB signed request var passed in via $_REQUEST
	public static $signed_request;
	
	// FB login/logout URLS
	public static $loginURL;
	
	public static function bootstrap_api(){
		
		if( !FB_lib::$facebook ){
			FB_lib::$facebook = new Facebook(array(
			  'appId'  => APP_ID,
			  'secret' => FB_SECRET,
			  'cookie' => false,
			  'domain' => APP_DOMAIN
			));
		}
	}
	
	// profile_id is passed along in addtition to the signed request
	// variable when the user is on a page
	public static function viewing_a_page(){
		return( self::$signed_request['profile_id'] && 
			( self::$signed_request['profile_id'] && self::$signed_request['user_id']) 
		);	
	}
	
	
	// for sanity call this function to ensure the FB user actually admins the page
	// to prevent any kind of URL hijacking that might allow users to do stuff
	// to pages they don't admin - ensures that the page_id passed in on the URL
	// actually shows up in the list of pages for which the user is an administrator
	public static function is_legit_page_admin( $page_id ){
		self::get_me();
		
		// if there is not FB user data at all, deny
		if( !self::$me['id'] ) return false;
		
		// if this page id is in the list of pages administered, grant
		foreach( self::$accounts['data'] as $k=>$page ){
			if( $page_id == $k ) return true;
		}
		
		// default deny
		return false;
		
	}
	
	// abstracts  user has done something and we've passed this variable
	// on the querystring upon that action... this tell the app how to respond
	// to user actions in the app, whatever those may be
	public static function handling_app_action(){
		return( isset( $_REQUEST['app_action'] ) );
	}
	
	// 
	public static function app_action_handler(){
	
		switch( $_REQUEST['app_action'] ){
			default:
				FB_view::profile_header();
				FB_view::default_page_tab_view();
				exit;
		}
		
	}
	
	// handles requests that end up at the facebook app side of things
	// this is FB app code, not a user page tab
	public static function app_view_handler(){
		FB_view::page_header();
		FB_view::app_header();
		FB_view::default_app_view();
		FB_view::page_footer();
		
	}
	
	// handles 
	public static function initiate_auth(){
		
		// get and decode the signed request from Facebook
		self::$signed_request = self::$facebook->getSignedRequest();
		self::$signed_request = parse_signed_request( $_REQUEST['signed_request'], FB_SECRET );
		self::$session = self::$facebook->getSession();
		
		if ( self::$session ) {
		 try {
		    if( self::viewing_a_page() ){
				
				// fetch the page data from the graph api
				$access_token = FB_lib::get_access_token();
				self::$me = self::broker_graph_request( self::$signed_request['profile_id'], $access_token );
				
			}
			else {
				// fetch the user's data from the graph api
				self::$me = self::$facebook->api('/me');
				
			}
			
			echo '<h2>/me</h2>';
			mpuke( self::$me );
			
			
		  } catch (FacebookApiException $ex) {
			// something bad happened
			mpuke( $ex );
			exit;
		  }
		}
		else {
			// there is no valid FB user sesssion
			// initiate a redirect to set up the session
			self::top_redirect();
			
		}
		
		// app has been added if there is an oauth_token
		
		if( self::$signed_request['oauth_token'] ){ 
			
			// if there is a signed request/oauth token then the user has added the app
			self::$user['has_added_app'] = 1;
			self::$oauth_token = self::$signed_request['oauth_token'];
			
			// fetches the pages the user is an admin of, this is what Facebook
			// is expecting us to do with our app, let people make page tabs for
			// the pages they administer
			self::get_accounts();
			
			echo '<h2>pages i admin</h2>';
			mpuke( self::$accounts );
			
			// when the user is hitting the app from an installation on a page tab
			if( self::viewing_a_page() ) { 
				FB_view::page_tab_header();
				FB_view::default_page_tab_view();
				exit;
			}
			else{
				// the name profile_action might be misleading, leftover from 
				// when this was used on profile tabs
				// this section handles the admin stuff including the view and 
				// handling admin actions
				if( isset( $_REQUEST['profile_action'] ) ){
					self::app_action_handler();
				}
				self::app_view_handler();
			}
			
		}
		else{
			// app has not been added if there is no oauth_token
			// so this will kick off the add-app permissions request
			// and once the user has consented they will end up running the
			// post authorize function
			self::$user['has_added_app'] = 0;
			self::oauth_authorize();
			// mpuke($_REQUEST);
		}
		return true;
	}
	
	
	public static function top_redirect( $url=false ) {

		if( !$url ){
			$url = self::$facebook->getLoginUrl(array(
				'canvas'    => 1,
			    'fbconnect' => 0,
			    'req_perms' => self::$perms_req
			));
		}
		// performs a javascript redirect at the top window level
		// to the login url
		echo "<script type=\"text/javascript\">\ntop.location.href = \"$url\";\n</script>";
	    exit;
  	}

	// this redirects the app at the top window level to the FB authorize app
	// workflow- essentially what gives you the page to grant the app access
	// via the facebook API
	// updated to not use fb_XXX vars and to use signed request migration
	public static function oauth_authorize(){
		
		$authorize_url  = 'https://graph.facebook.com/oauth/authorize';
		$authorize_url .= '?client_id=' . APP_ID;
		$authorize_url .= '&redirect_uri='. urlencode( AUTH_URL . '?authorize=1&is_facebook=1' );
		$authorize_url .= '&scope=' . self::$perms_req;
		$authorize_url .= '&display=page';
		
		?>
		
		<script type="text/javascript">
			top.location.href = '<?php echo $authorize_url; ?>';
		</script>
		<?php
		exit;
	}
	
	// UNUSED, can probably be deleted - also unfinished
	// updated to not use fb_XXX vars and to use signed request migration
	public static function exchange_session( $cookie ){
		// self::$oauth_token = '';
		// $code = $_REQUEST['code'];
		// if( $code ) return; 
		$type = "client_cred";
		$exchange_url  = 'https://graph.facebook.com/oauth/exchange_sessions';
		$exchange_url .= '?type=' . $type;
		$exchange_url .= '&client_id=' . APP_ID;
		$exchange_url .= '&client_secret=' . FB_SECRET;
		$exchange_url .= '&sessions=';
		// $exchange_url .= '&redirect_uri='. urlencode( AUTH_URL . '?authorize=1&is_facebook=1' );
		
		
		?>
		
		<script type="text/javascript">
			top.location.href = '<?php echo $authorize_url; ?>';
		</script>
		<?php
		exit;
	}
	
	// puts an access token in the SESSION
	public static function get_access_token(){
		
		// TO DO - how do we handle this more reobustly?
		if( !self::$signed_request['oauth_token'] ) return false;
		
		$type = 'client_cred'; // 'web_server'; // 'user_agent'; // 'client_cred';
		$token_endpoint = 'oauth/access_token';
		
		$token_url .= '?client_id=' 	. APP_ID;
		$token_url .= '&redirect_uri='	. urlencode( AUTH_URL . '?authorize=1&is_facebook=1' );
		$token_url .= '&client_secret='	. FB_SECRET;
		$token_url .= '&type=' 			. $type;
		$token_url .= '&code'			. self::$signed_request['oauth_token'];
 		
		
		$response = /*json_decode( */
			fb_curl_request(
				GRAPH_ENDPOINT . $token_endpoint . $token_url
			);
		// );
		
		// we're sending a curl request and parsing out the return variables.
		// those return variables will hold the user session 9or an error if one occurs)
		parse_str( $response, $args );
		
		
		if( $args['error'] ){
			// TO DO - gracefully handle errors
			
		}
		else
		{
			$_SESSION['access_token'] = self::$access_token = $args['access_token'];
		}
		return self::$access_token;

	}
	
	// makes a call to the graph API based on the endpoint passed in
	protected static function broker_graph_request( $endpoint_function = false, $access_token = false ){
		
		if( !$access_token )
			$token = self::$signed_request['oauth_token']; // self::$access_token;
		else
			$token = $access_token;
			
		// if not function is defined to request or
		// if there is no oauth_token we have to ditch out of this
		if (!$endpoint_function || !$token ) return false;
		
		// mpuke( $token );
		$request_uri = GRAPH_ENDPOINT . $endpoint_function . "?access_token=" . $token;
		// mpuke( $request_uri );
		
		try{
			$response = json_decode( fb_curl_request( $request_uri ) );
		} catch (Exception $ex) { // FacebookApiException
			// TO DO - comment out, handle more gracefully.
			mpuke( $ex );
		}
		
		return $response;
	}
	
	// populates the static variable $me with the result from /me from the
	// graph API, this was changed a bit in my implementation and is now not used
	// but I'm leaving it in for the time being. clean up and delete later.
	public static function get_me(){
		
		if( !self::$me ){
			// self::$me = self::broker_graph_request( 'me' );
			// self::$me = self::$facebook->api('/me');
		}		
		return self::$me;
	}
	
	// gets the first page of the logged in user's friends.
	public static function get_friends(){
		
		// make call to GRAPH/me/friends and populate
		// self::$friends
		
		if( !self::$friends )
			self::$friends = self::broker_graph_request( 'me/friends' );
		
		return self::$friends;
	}
	
	// populates the static variable $accounts with the
	// facebook pages administered by the user, removes Applications, and
	// determines if the app is installed for each and gets a link to the FB page
	public static function get_accounts(){
		
		
		// $access_token = FB_lib::get_access_token();
		$oauth_token = FB_lib::$oauth_token;
		
		if( !self::$accounts['data'] ){
			
			try{
				self::$accounts = FB_lib::$facebook->api( '/me/accounts?access_token=' . $oauth_token );
			
				self::remove_applications();
			
			
			
				foreach( self::$accounts['data'] as $k=>$page ){
					// self::fix_wayward_sort_orders( self::$accounts['data'][$k]['id'] );
				}
			
				self::reorder_accounts_array();
				self::get_app_installed_and_link();
			
				// moved this to FB_view::display_new_admin to defer until after add/delete/order functions
				// are called otherwise, shops are fetched and put into the FB_lib::$accounts array
				// before user actions are performed
				// self::get_page_stores();
			} catch (FacebookApiException $ex) {
				// mpuke( $ex );
			}
		}
		
		// mpuke( self::$accounts['data'] );
		
	}
	
	public static function reorder_accounts_array(){
		foreach( self::$accounts['data'] as $k=>$page ){
			self::$accounts['data'][$page['id']] = self::$accounts['data'][$k];
			unset( self::$accounts['data'][$k] );
		}
	}
	
	// since facebook jsut arbitrarily made the /me/accounts call (end of Oct)
	// return apps and pages and other things.. I need to 
	// not query for info about Applications to speed this up.
	// the get_accounts FB code used to only return pages
	public static function remove_applications(){
		foreach( self::$accounts['data'] as $k=>$page ){
			
			if( "Application" == self::$accounts['data'][$k]['category'] ){
				unset( self::$accounts['data'][$k] );
			}
		}
	}
	
	public static function get_app_installed_and_link(){
		$in_cnt = 0;
		foreach( self::$accounts['data'] as $k=>$page ){
			
			if( $in_cnt > 0 ) $in .= ',';
			$in .= $page['id']; $in_cnt++;
			
		}
		
		// wrapped this up into one call and removed Applications first
		// and it takes about 1/10th of the time
		$query = "
			SELECT page_id, has_added_app, page_url
			FROM page 
			WHERE page_id IN (" . $in . ");
		";
		
		// mpuke( $query );
		
		// $time_start = microtime(true);
		$result=FB_lib::$facebook->api(array(
		  'method' => 'fql.query',
		  'query' => $query
		));
		// $time_end = microtime(true);
		// $time = $time_end - $time_start;
		// echo '<div>fb page fql query: ' . $time . 's</div>';
		
		foreach( $result as $page ){
			self::$accounts['data'][$page['page_id']]['has_added_app'] = $page['has_added_app'];
			self::$accounts['data'][$page['page_id']]['link'] = $page['page_url'];
		}
		
	}
	
}

// ******************************
/* helper functions */

// finally, bootstrap the FB api, replaces FB_lib::ensure__api()
// ensures that the static var $facebook has an instantiaite Facebook
// object from the php-sdk
FB_lib::bootstrap_api();

function parse_signed_request($signed_request, $secret) {
	list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

	 // decode the data
	 $sig = base64_url_decode($encoded_sig);
	 $data = json_decode(base64_url_decode($payload), true);

	 if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
	   error_log('Unknown algorithm. Expected HMAC-SHA256');
	   return null;
	 }

	 // check sig
	 $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
	 if ($sig !== $expected_sig) {
	   error_log('Bad Signed JSON signature!');
	   return null;
	 }

	 return $data;
}

function base64_url_decode($input) {
  	return base64_decode(strtr($input, '-_', '+/'));
}

function mpuke($obj, $wrap='pre'){
	print '<div style="text-align:left!important">';
  	print '<' . $wrap . '>';
  	print_r($obj);
  	print '</' . $wrap . '>';
	print '</div>';
}


// function to prevent sql injection on inserts
function sql_safe($string){
	if($string !== '') {
      	return '\'' . addslashes($string) .  '\'';
    } else {
      	return 'NULL';
    }
}

// this function brokers the call to the graph API
// currently params is not used
function fb_curl_request( $url, $params = array() ){
    // configure CURL call using "get" method
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
?>