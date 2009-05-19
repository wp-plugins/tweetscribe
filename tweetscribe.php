<?php

/*
Plugin Name: TweetScribe
Plugin URI: http://tweetscribe.me/downloads/
Description: Subscripbe to a blog via Twitter
Author: stratosg
Version: 1.1
Author URI: http://www.stratos.me
*/

add_action('publish_post', 'tweetscribe_published');

function tweetscribe_published($postid){
	$username = get_option('tweetscribe_username');
	$password = get_option('tweetscribe_password');
	$text = stripslashes(get_option('tweetscribe_text'));
	$api_key = get_option('tweetscribe_key');
	
	$post = get_post($postid);
	
	if($post->post_date == $post->post_modified){//only tweet when publish not when updating
		$permalink = get_permalink($postid);
		$results = file_get_contents('http://api.bit.ly/shorten?version=2.0.1&longUrl='.$permalink.'&login=bitlyapidemo&apiKey=R_0da49e0a9118ff35f52f629d2d71bf07&format=xml');
		preg_match("/<shortUrl>(.*)<\/shortUrl>/", $results, $matches);
		$tweet = str_replace("URL", $matches[1], str_replace("TITLE", $post->post_title, $text));
		$tweet_result = file_get_contents('http://tweetscribe.me/api/action/tweet/?uname='.$username.'&key='.$api_key.'&tweet='.urlencode($tweet));
	}
}

function tweetscribe_install(){
	add_option('tweetscribe_username', '');
	add_option('tweetscribe_password', '');
	add_option('tweetscribe_text', '"TITLE" > URL');
	add_option('tweetscribe_key', '');
}

function tweetscribe_uninstall(){
	delete_option('tweetscribe_username');
	delete_option('tweetscribe_password');
	delete_option('tweetscribe_text');
	delete_option('tweetscribe_key');
}

register_activation_hook(__FILE__, 'tweetscribe_install');
register_deactivation_hook(__FILE__, 'tweetscribe_uninstall');


//ADMIN

add_action('admin_menu', 'tweetscribe_plugin_menu');

function tweetscribe_plugin_menu() {
	add_options_page('TweetScribe', 'TweetScribe', 8, __FILE__, 'tweetscribe_admin_page');
}

function tweetscribe_admin_page(){
	if(isset($_GET['op']) && $_GET['op'] == 'save'){
		update_option('tweetscribe_username', $_POST['tweetscribe_username']);
		update_option('tweetscribe_password', $_POST['tweetscribe_password']);
		update_option('tweetscribe_text', $_POST['tweetscribe_text']);
		update_option('tweetscribe_key', $_POST['tweetscribe_key']);
	}
	
	$username = get_option('tweetscribe_username');
	$password = get_option('tweetscribe_password');
	$text = get_option('tweetscribe_text');
	$api_key = get_option('tweetscribe_key');
	
	if($api_hey == '' && ($username != '' && $password != '')){
		$blog_url = get_bloginfo('url');
		$bname = get_bloginfo('name');
		$description = get_bloginfo('description');
		$email = get_bloginfo('admin_email');
		$twscribe_url = 'http://tweetscribe.me/api/action/get_key/?url='.urlencode($blog_url).'&uname='.$username.'&pwd='.$password.'&email='.$email.'&desc='.urlencode($description).'&bname='.urlencode($bname);
		$api_key = tweetscribe_curl($twscribe_url);
		if(strpos($api_key, 'ERROR') === FALSE){
			update_option('tweetscribe_key', $api_key);
		}
		else {
			$api_error_msg = 'There was an error authenticating you (Are you sure about your username/password?)';
		}
	}

	?>
	<h2>TweetScribe Settings</h2>
	<h3>Twitter</h3>
	<form name="tweetscribe_settings" action="?page=tweetscribe/tweetscribe.php&amp;op=save" method="post">
	<div>
		Username <input type="text" name="tweetscribe_username" value="<?php echo $username;?>">
		<br />
		Password <input type="password" name="tweetscribe_password" value="<?php echo $password;?>">
	</div>
	<div>Please make sure you are following <a href="http://twitter.com/twscribe">@twscribe</a> or else an error #102 will result on saving.</div>
	<h3>Tweet</h3>
	<div valign="middle">
		<table>
			<tr>
				<td>
					<textarea name="tweetscribe_text" cols=75 rows=5><?php echo stripslashes($text);?></textarea>
				</td>
				<td valign="top">
					Shortcodes:
					<br />
					TITLE : The title of the post
					<br />
					URL : The shortened URL of the permalink
				</td>
			</tr>
		</table>
	</div>
	<h3>TweetScribe Key</h3>
	<div valign="middle">
		<input type="text" name="tweetscribe_key" value="<?php echo $api_key;?>" size=35 maxlength=32 disabled>
		<?php echo ($api_key == '' ? 'Will be filled up once you fill in your twitter username/password above' : ''); ?>
		<?php echo (isset($api_error_msg) ? $api_error_msg : ''); ?>
	</div>
	<input type="submit" value="Save">
	</form>
	<h3>TweetScribe Badge</h3>
	<p>You can have the widget provided with the plugin show the badge or you can include the following HTML code on any place you want!</p>
	<textarea rows=5 cols=50>
		<?php if($api_key != ''){ ?>
			<a href="http://tweetscribe.me/user/subscribe/?blog=<?= bloginfo('url');?>"><img alt="tweetscribe" src="http://tweetscribe.me/api/action/badge/?blog=<?= bloginfo('url');?>"></a>
		<?php }?>
	</textarea>
	<?php
}

function tweetscribe_curl($url){
	$curl = curl_init();
		 
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($curl, CURLOPT_URL, $url);
	 
	$result = curl_exec($curl);
	curl_close($curl);
	
	return $result;
}

//WIDGET

function widget_tweetscribewidget($args) {
	extract($args);
	echo $before_widget;
	echo $before_title;?>TweetScribe<?php echo $after_title;?>
	<center>
		<a href="http://tweetscribe.me/user/subscribe/?blog=<?php bloginfo('url');?>">
			<img alt="tweetscribe_counter" src="http://tweetscribe.me/api/action/badge/?blog=<?php bloginfo('url'); ?>">
		</a>
	</center>
	<br />
	<?php
	echo $after_widget;
}

function tweetscribewidget_init()
{
  register_sidebar_widget(__('TweetScribe'), 'widget_tweetscribewidget');
}
add_action("plugins_loaded", "tweetscribewidget_init");

?>