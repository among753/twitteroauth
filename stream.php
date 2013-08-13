<?php
/**
 * Created by PhpStorm.
 * User: sasaki
 * Date: 13/08/13
 * Time: 11:16
 *
 *
 * http://nuke.hateblo.jp/entry/20120202/1328150368
 * http://lealog.hateblo.jp/entry/2013/03/10/100845
 * http://www.softel.co.jp/blogs/tech/archives/3929
 */



require_once "./config.php";


// APIのURL
$url = 'https://stream.twitter.com/1.1/statuses/filter.json';
//$url = 'http://webengibeer.com/';

// リクエストのメソッド
$method = 'GET';

// パラメータ
$post_parameters = array(
);
$get_parameters = array(
//	'locations' => '132.2,29.9,146.2,39.0,138.4,33.5,146.1,46.20',
	'track'     => 'WordPress'
//	'track'     => urlencode('なう')
);
$oauth_parameters = array(
	'oauth_consumer_key' => CONSUMER_KEY,
	'oauth_nonce' => microtime(),
	'oauth_signature_method' => 'HMAC-SHA1',
	'oauth_timestamp' => time(),
	'oauth_token' => OAUTH_TOKEN,
	'oauth_version' => '1.0',
);

// 署名を作る
$a = array_merge($oauth_parameters, $post_parameters, $get_parameters);
ksort($a);
$base_string = implode('&', array(
	rawurlencode($method),
	rawurlencode($url),
	rawurlencode(http_build_query($a, '', '&', PHP_QUERY_RFC3986))
));
$key = implode('&', array(rawurlencode(CONSUMER_SECRET), rawurlencode(OAUTH_TOKEN_SECRET)));
$oauth_parameters['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $key, true));


//タイムアウトをしないように設定
set_time_limit(0);

header("content-type: text/html; charset=utf-8");


// 接続＆データ取得
// $fp = stream_socket_client("ssl://stream.twitter.com:443/"); でもよい
$fp = fsockopen("ssl://stream.twitter.com", 443);
//$fp = fsockopen(PROXY_HOST, PROXY_PORT);//http proxyがSSHでのCONNECTを許可してないとhttps://に接続できない。
if ($fp) {
	fwrite($fp, "GET " . $url . ($get_parameters ? '?' . http_build_query($get_parameters) : '') . " HTTP/1.0\r\n"
		. "Host: stream.twitter.com\r\n"
		. 'Authorization: OAuth ' . http_build_query($oauth_parameters, '', ',', PHP_QUERY_RFC3986) . "\r\n"
		. "\r\n");

/*	while (!feof($fp)) {
		echo fgets($fp);
		ob_flush();
		flush();
		sleep(1);
	}*/
	while(!feof($fp)){
		$tweet= json_decode(fgets($fp), true);
		$user = $tweet['user']['screen_name'];
		$text = $tweet['text'];

//		var_dump($tweet);//NULLもあるよ

		if(!empty($user)){
			$timeline = '<strong>'.$user.'</strong> : '.$text.PHP_EOL;
			echo $timeline . "<br>";
			ob_flush();
			flush();
//			sleep(1);
		}
	}


	fclose($fp);
}