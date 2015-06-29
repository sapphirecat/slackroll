// A few things we should require
var http = require('http');
var url = require('url');
var strftime = require('strftime');

// Global configuration
var serverPort = 1570;
var slackToken = null;

var slackRollCallback = function(request,response){
	// Parse the incoming URL
	var incoming = url.parse(request.url,true).query;

	// Gotta have a log
	console.log('Request from '+request.connection.remoteAddress+' at '+strftime('%F %T',new Date()));

	// Are we supposed to respond to only a certain Slack
	if(typeof(slackToken) !== 'undefined' && slackToken !== null && slackToken !== ''){
		if(typeof(incoming.token) === 'undefined' || incoming.token !== slackToken){
			console.log('Invalid token given. No response issued.'); return;
		}
	}

	// Remove whitespace from the diceroll request
	incoming.text = incoming.text.replace(/ /g,'');
	
	// Make sure this looke like an approptiate roll string
	var regex = new RegExp('^\\d+d\\d+([+-]\\d+)?$');
	if(regex.test(incoming.text) === false){
		response.end('Your roll was not well formatted. Try something like *4d6-2*.');
		return;
	}
	
	// Declare the mathy variables here so we can work on them below
	var numDice = 0;
	var numSides = 0;
	var plus = 0;
	var minus = 0;
	var plusExp = new RegExp('\\+');
	var minusExp = new RegExp('\\-');

	// Split the string into usable parts that we use in math later
	var splitter = incoming.text.split('d');
	numDice = parseInt(splitter[0]);

	if(splitter[1].search(plusExp) > 0){
		// We're adding a number!
		splitter = splitter[1].split('+');
		numSides = parseInt(splitter[0]);
		plus = parseInt(splitter[1]);
	}else if(splitter[1].search(minusExp) > 0){
		// We're subtracting a number!
		splitter = splitter[1].split('-');
		numSides = parseInt(splitter[0]);
		minus = parseInt(splitter[1]);
	}else{
		// There's no modifier
		numSides = parseInt(splitter[1]);
	}

	// Make sure we are rolling at least one die with at least one side
	if(numDice < 1 || numSides < 2){
		response.end('You must roll at least one die with at least two sides. You tried *'+incoming.text+'*');
		return;
	}	

	// Roll each die, store the results
	var dieRolls = [];
	var total = 0;
	var responseString = '';
	for(i=0; i<numDice; i++){
		var thisRoll = Math.floor(Math.random()*numSides)+1;
		total += thisRoll;
		dieRolls.push(thisRoll);
	}

	// Apply the modifiers
	var modifiedTotal = total + plus - minus;
	
	// Write up the response string
	var resultString = incoming.user_name+' rolled '+incoming.text+' and got (';
	for(i=0; i<dieRolls.length; i++){
		if(i===dieRolls.length-1){
			resultString = resultString+dieRolls[i]+') ';
		}else{
			resultString = resultString+dieRolls[i]+', ';
		}
	}
	if(plus > 0) resultString = resultString+'+ '+plus+' ';
	if(minus > 0) resultString = resultString+'- '+minus+' ';
	resultString = resultString+'= '+modifiedTotal;
	
	// Send the response back to Slack
	response.end(resultString);
}

var slackRollServer = http.createServer(slackRollCallback);
slackRollServer.listen(serverPort,"0.0.0.0");
console.log('slackRoll running on port '+serverPort);
