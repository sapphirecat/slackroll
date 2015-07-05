<?php
require_once implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'vendor', 'autoload.php']);

use \GuzzleHttp\Client;

// Try really hard to get a username so we don't have to hard-code a default
// token. (The server, by default, ignores tokens though....)
$id = $_SERVER['USERNAME'] ?: $_SERVER['LOGNAME'] ?: basename($_SERVER['HOME'] ?: $_SERVER['HOMEPATH']);

// Configuration.
$serverHost = '127.0.0.1';
$serverPort = 1570;
$slackToken = "test_$id";

// Build fake request.
$request = array(
		'token' => $slackToken,
		'text' => '3d6+7',
		'user_name' => 'slackuser',
		'channel_name' => 'general',
		);
$client = new Client(array(
			'base_uri' => "http://$serverHost:$serverPort/",
			));
$response = $client->get('/?'.http_build_query($request));

$body = $response->getBody();
if ($response->getStatusCode() >= 400) {
	echo "HTTP {$response->getStatusCode()} {$response->getReasonPhrase()}\n";
} else {
	echo "OK!\n";
}

echo "$body\n";

