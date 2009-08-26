<?php
/*
Plugin Name: svel.to service plugin
Plugin URI: http://blog.mikamai.com
Description: It shortens your links when you post a content
Version: 0.0.1
Author: Vivien Schilis
Author URI: http://vivienschilis.me
*/

define('SVELTO_API_SHORTEN_URL', 'http://svel.to');

function svelto_init(){
	add_filter ( 'publish_post', 'shorten_all_links' );		
}

function svelto_shorten_link ($link) {
		// Is it already shorten?
		$regexp=SVELTO_API_SHORTEN_URL;
		$regexp = "/".str_replace("/","\/",$regexp)."/";
		if(preg_match($regexp, $link)) return NULL;
		
		$api = SVELTO_API_SHORTEN_URL.'/links';
		$postdata = json_encode(array("link" => $link ));
		
		$timeout = 10;
		$request = curl_init();
		curl_setopt($request, CURLOPT_URL, $api);
		curl_setopt($request, CURLOPT_ENCODING, '');
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($request, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($request, CURLOPT_POST, 1);
		curl_setopt($request, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-type: application/json'));
		curl_setopt($request, CURLOPT_POSTFIELDS, $postdata);

		$result = curl_exec($request);
		curl_close($request);

		return json_decode($result);
}


function shorten_all_links ($post_ID) {
	
	$my_post = array();
	$my_post['ID'] = $post_ID;
	
	$content = extract_content_links(stripslashes($_POST['post_content']));
	$my_post['post_content'] = $content;
		
	remove_action('publish_post', 'shorten_all_links');
	wp_update_post($my_post);
	return $post_ID;
}

function shorten_external_link ($matches) {
	$new_url = $matches[2] . '://' . $matches[3];

	// not shorten a link with the same url that the wordpress site
	$regexp=get_bloginfo( 'wpurl' );
	$regexp = "/".str_replace("/","\/",$regexp)."/";
	if(!preg_match($regexp, $new_url)){
		$res = svelto_shorten_link($new_url);
		if($res && $res->url)
			$new_url = $res->url;
	}
	
	return '<a href="' . $new_url  . '" ' . $matches[1] . $matches[4] . '>' . $matches[5] . '</a>';
}


function extract_content_links($text) {
	$pattern = '/<a (.*?)href="(.*?):\/\/(.*?)"(.*?)>(.*?)<\/a>/i';
	$text = preg_replace_callback($pattern,'shorten_external_link',$text);
			

	return $text;
}

add_action('init', 'svelto_init');
?>