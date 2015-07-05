<?php
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'vendor', 'autoload.php']);

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Handler\CurlMultiHandler;
use \GuzzleHttp\HandlerStack;

use \Monolog\Logger;
use \Monolog\Formatter\LineFormatter;
use \Monolog\Handler\StreamHandler;

use \React\EventLoop\Factory as EventLoop;
use \React\Socket\Server as Listener;
use \React\Http\Server;


// ---------- Configuration ----------
$serverPort = 1570;
$slackToken = null;
$webhookUrl = null;
$logLevel   = Logger::INFO;
$tz         = 'America/New_York';
// -----------------------------------


// Set up main objects/environment
if (! ini_get('date.timezone')) {
	date_default_timezone_set($tz);
}

$log = new Logger(basename(__FILE__, '.php'));
$handler = new StreamHandler(STDOUT, $logLevel);
$formatter = new LineFormatter(null, null, false, true); // skip adding `[]` if $extra is empty
$handler->setFormatter($formatter);
$log->pushHandler($handler);
unset($handler, $formatter);

$ev = EventLoop::create();
$socket = new Listener($ev);
$http = new Server($socket);

// since we can't push an event loop to use into Guzzle, we have to poll it.
// interval of 0 pegs the CPU; since we're using this only for errors,
// responsiveness isn't a big deal, and we'll minimize wakeups instead.
//
// Guzzle<->React bridge details found at:
// http://stephencoakley.com/2015/06/11/integrating-guzzle-6-asynchronous-requests-with-reactphp
$guzzleHandler = new CurlMultiHandler();
$pollFunction = function () {
	$this->tick();
};
$ev->addPeriodicTimer(0.3, \Closure::bind($pollFunction, $guzzleHandler, $guzzleHandler));
unset($pollFunction);


// HTTP responder
$responder = function ($request, $response) use ($ev, $log, $slackToken, $webhookUrl, $guzzleHandler) {
	// Parse the incoming URL
	$incoming = $request->getQuery();
	if (! isset($incoming['token'])) {
		$incoming['token'] = null;
	} elseif (! preg_match('#^[-+/.{}\\w]+$#', $incoming['token'])) {
		error_log("malformed incoming Slack token: $incoming[token]");
	}

	// Gotta have a log
	$log->addInfo("request from $request->remoteAddress");

	// Are we supposed to respond to only a certain Slack
	if ($slackToken !== null && $incoming['token'] !== null) {
		if ($slackToken !== $incoming['token']) {
			$log->addNotice('Invalid token received: "Forbidden" response issued.');
			$response->writeHead(403);
			$response->end();
			return;
		}
	}

	// Prepare for real responses
	$response->writeHead();

	// Remove whitespace from the diceroll request
	$incoming['text'] = str_replace(' ', '', $incoming['text']);

	// Make sure this looke like an approptiate roll string
	if (preg_match('/^(\\d+)d(\\d+)([+-]\\d+)?$/', $incoming['text'], $m)) {
		// Declare the mathy variables here so we can work on them below
		$numDice = (int) $m[1];
		$numSides = (int) $m[2];
		$modifier = (int) ltrim($m[3], '+');

		// Make sure we are rolling at least one die with at least one side
		if ($numDice < 1 || $numSides < 2) {
			$response->end("You must roll at least one die with at least two sides. You tried *$incoming[text]*");
			return;
		}

		// Roll each die, store the results
		$dieRolls = [];
		$total = 0;
		$responseString = '';
		for ($i=0; $i<$numDice; $i++) {
			$thisRoll = mt_rand(1, $numSides);
			$total += $thisRoll;
			$dieRolls[] = $thisRoll;
		}

		// Apply the modifiers
		$modifiedTotal = $total + $modifier;

		// Write up the response string
		$resultString = "$incoming[user_name] rolled $incoming[text] and got (";
		$resultString .= implode(', ', $dieRolls);
		$resultString .= ') ';
		if($modifier > 0) $resultString .= '+ ' . $modifier . ' ';
		elseif($modifier < 0) $resultString .= '- ' . (-$modifier) . ' ';
		$resultString .= '= ' . $modifiedTotal;

	} else {
		$response->end('Your roll was not well formatted. Try something like *4d6-2*.');
		return;
	}


	// We can't respond to a private group with a webhook, unfortunately
	if ($incoming['channel_name'] === 'privategroup' || ! $webhookUrl) {
		$response->end($resultString);
		return;
	}

	// Send the response back to Slack
	$returnPayload = array(
		'channel' => "#$incoming[channel_name]",
		'username' => 'slackRoll',
		'icon_emoji' => ':game_die:',
		'text' => $resultString,
	);

	// Send a POST to the webhook in maximum wtf style (the client has json?)
	$client = new Client(array( # TODO: move creation outside of request loop
				'json' => $returnPayload,
				'handler' => HandlerStack::Create($guzzleHandler),
				));
	$promise = $client->postAsync($webhookUrl);
	$promise->then(
			null, // we don't care about success
			function (RequestException $ex) use ($log) {
				$log->addError("webhook error: {$ex->getMessage()}");
			});
};


// Now assemble our pre-created objects into an actual server stack.
// note example at https://github.com/reactphp/event-loop
$http->on('request', $responder);
$socket->listen($serverPort);
$ev->run();
