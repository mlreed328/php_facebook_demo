<?php
// buffer start in case of abrupt facebook redirect
ob_start();
$time_start = microtime(true);

// required to make certain browsers handle 3rd party cookies
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

include 'lib/FB_lib.class.php';
include 'lib/FB_view.class.php';

if( isset( $_REQUEST['authorize'] ) ){
	FB_lib::post_authorize();
	ob_end_flush(); 
	exit;
}

// this does the session handling, login, etc
if( !FB_lib::initiate_auth() ){
	// some kind of error occurred
	echo '<p>Thank you for your patience as we fix some bugs.</p>';
}
// otherwise, output is sent to the browser from other functions


// footer timer and flush/end
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "generated in " . $time . 's';
ob_end_flush(); exit;

?>