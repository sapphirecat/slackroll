<?php
require_once implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'vendor', 'autoload.php']);

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Handler\CurlMultiHandler;
use \GuzzleHttp\HandlerStack;
use \Psr\Http\Message\ResponseInterface;

use \React\EventLoop\Factory as EventLoop;

$ev = EventLoop::create();

// bridge Guzzle<->React, as described at:
// http://stephencoakley.com/2015/06/11/integrating-guzzle-6-asynchronous-requests-with-reactphp
$guzzleHandler = new CurlMultiHandler();
$pollFunction = function () {
	$this->tick();
};
$ev->addPeriodicTimer(0.3, \Closure::bind($pollFunction, $guzzleHandler, $guzzleHandler));


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
			'handler' => HandlerStack::Create($guzzleHandler),
			'timeout' => 4,
			));
$promise = $client->getAsync('/?'.http_build_query($request));

$promise->then(function (ResponseInterface $response) {
		$body = $response->getBody();
		if ($response->getStatusCode() >= 400) {
			echo "HTTP {$response->getStatusCode()} {$response->getReasonPhrase()}\n";
		} else {
			echo "OK!\n";
		}

		echo "$body\n";
		exit(0);
	},
	function (RequestException $ex) {
		echo "Error: ", $ex->getMessage(), "\n";
		exit(1);
	});

// launch event loop
$ev->run();

