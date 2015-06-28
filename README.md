# slackroll
<b>SlackRoll is a diceroller designed for integration with Slack.</b><br/>
<br/>
You type this:<br/>
<img src="http://i.imgur.com/8yUrbeT.png"/><br/>

You get this:<br/>
<img src="http://i.imgur.com/OctHF5Z.png"/><br/>

<b>Installation and configuration</b><br/>
1. Clone the repo.<br/>`git clone https://github.com/LegendaryLinux/slackroll.git`<br/><br/>
2. Install the sole dependency<br/> `cd slackroll && npm install -g strftime`<br/><br/>
3. Take a look inside `slackRoll.js`. You'll find three configuration options. Set them as you see fit.<br/>
  `serverPort`: The port you want the server to listen on<br/>
  `verifyServerToken`: Checks that the Slack token sent matches the one you specify. Can be `true` or `false`<br/>
  `slackToken`: The token associated with your Slack integration<br/><br/>
4. Create a new integration for your Slack. You'll want a new Slash Command. Four settings are important here.
<ul>
  <li><b>Command</b>: This should be what you want to use to tell Slack to listen for a diceroll. You can use whatever you want.</li>
  <li><b>URL</b>: This is the endpoint where your Node.js server will be listening for requests. If you're on port 1500, this should be: http://yourserver.com:1500/. Note that the slash after the port number is mandatory for slack to send a GET request.</li>
  <li><b>Method</b>: This must be GET.</li>
  <li><b>Token</b>: This is the token referenced above. You don't need to do anything with it unless you want to make sure only your team uses the diceroller.</li>
</ul><br/>
5. Once the integration is set up, just start the server.<br/>
`node slackRoll.js`<br/><br/>

That's it. Enjoy your dice!
