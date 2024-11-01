<?php
/**
 * Plugin Name: WP Comments to FB User Dashboard
 * Description: Give you che possibility to send WP comments (with your custom comments form) to facebook user dashboard. It's not providing facebook login (provided from many other plugins) but only the comments connection. Login to fb required with publish_stream permissions. Require CURL.
 * Version: 0.1
 * Author: Marco Buttarini
 * Author URI: http://marbu.org
 *
 */


// Runs just after a comment is saved in the database. 
add_action( 'comment_post', 'marbu_comment_tofb' );
add_action('admin_menu', 'marbu_tofb_plugin_menu');

function marbu_tofb_plugin_menu() {
  add_options_page('Comments to Facebook', 'Comments to Facebook', 'manage_options', 'wp_comments_to_facebook', 'wp_comments_to_facebook');
}

function wp_comments_to_facebook(){
  if($_REQUEST['action'] == "save"){
    $arrfb['fbappid']=$_REQUEST['fbappid'];
    $arrfb['fbsecret']=$_REQUEST['fbsecret'];
    $arrfb['defimg']=$_REQUEST['defimg'];
    update_option("marbu_wp_comments_to_facebook",$arrfb);
  }
  
  $optval=get_option("marbu_wp_comments_to_facebook");
  
  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }
?>
<div class="wrap">
<h2>Wp Comments To Facebook</h2>
<form>
<input type="hidden" name="page" value="<? echo $_REQUEST['page']; ?>">
<input type="hidden" name="action" value="save">
<p>Facebook App ID: <input type="text" name="fbappid" value="<? echo $optval['fbappid']; ?>"></p>
<p>Facebook Secret Key: <input type="text" name="fbsecret" value="<? echo $optval['fbsecret']; ?>"></p>
<p>Default Image: <input type="text" name="defimg" value="<? echo $optval['defimg']; ?>"></p>
<input type="submit">
</form>
</div>
<?
}

$optval=get_option("marbu_wp_comments_to_facebook");

$appid= $optval['fbappid']; 
$fbsecret= $optval['fbsecret'];
$defimg= $optval['defimg'];

function marbu_comment_tofb($comment_id, $status) {
  global $appid, $fbsecret,$defimg;
  // get the comment
  $commento=get_comment($comment_id); 
  $postid=$commento->comment_post_ID;
  // get the title
  $title=get_the_title($postid);
  $link=get_permalink($postid);
  $testo=$commento->comment_content;
  $image=$defimg;
  $desc=$testo;
  
  // check if i can send to fb user
  $fbstatus=trim(strip_tags(marbu_get_fb_auth($appid, $fbsecret)));
  
  if($fbstatus){
    $cookie = ctf_get_facebook_cookie($appid, $fbsecret);
    // send
    $access_token = $cookie['access_token'];
    $query = array('access_token' =>  $access_token, 'message' => ''.stripslashes($testo).'', 'link' => ''.($link).'', 'picture' => ''.$image.'', 'title' => ''.trim(stripslashes($title)).'', 'description' => ''.trim(stripslashes($desc)).'');
    $reqfb=  ctf_PostRequestToWorld("https://graph.facebook.com/me/feed", $query);
  }
}

function marbu_get_fb_auth($appid, $fbsecret){
  $cookie = ctf_get_facebook_cookie($appid, $fbsecret);
  $access_token = $cookie['access_token'];
  $facebookid = $cookie['uid'];
  $queryUrl = "/fql.query?access_token=" . $access_token;
  $queryUrl .="&query=SELECT%20publish_stream%20from%20permissions%20where%20uid%20=" . $facebookid;
  $rispfb = ctf_GetRequestToWorld("https://api.facebook.com/method",$queryUrl);
  return $rispfb;
}




function ctf_get_facebook_cookie($app_id, $application_secret) {
  $args = array();
  if (isset ($_COOKIE['fbs_' . $app_id]))  {
    parse_str(trim($_COOKIE['fbs_' . $app_id], '\\"'), $args);
    ksort($args);
    $payload = '';
    foreach ($args as $key => $value) {
      if ($key != 'sig') {
	$payload .= $key . '=' . $value;
      }
    }
    if (md5($payload . $application_secret) != $args['sig']) {
      return null;
    }
  }
  return $args;
}



function ctf_GetRequestToWorld ($host,$query)
{

  if(!function_exists('curl_init')) return false;
  $risposta = false;
  $curl = curl_init($host . $query);
  curl_setopt($curl, CURLOPT_PROXYPORT, 80);  
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,1000);
  curl_setopt($curl, CURLOPT_TIMEOUT,1000);
  curl_setopt($curl, CURLOPT_HEADER,0);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($curl, CURLINFO_HEADER_OUT, true); 
  $risposta = curl_exec($curl); 
  curl_close ($curl); 
  return $risposta;
 }



function ctf_PostRequestToWorld ($host,$query){
  if(!function_exists('curl_init')) return false;
  $risposta = false;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($curl, CURLOPT_PROXYPORT, 80); 
  curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
  curl_setopt($curl, CURLOPT_URL, $host);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,1000);
  curl_setopt($curl, CURLOPT_TIMEOUT,1000);
  curl_setopt($curl, CURLOPT_HEADER,0);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($curl, CURLINFO_HEADER_OUT, true);
  $risposta = curl_exec($curl);
  curl_close ($curl);
  return $risposta;
 }



?>