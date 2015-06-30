# slackroll
**SlackRoll is a diceroller designed for integration with Slack.**

You type this:  
<img src="http://i.imgur.com/p8jSZT5.png"/>

You get this:  
<img src="http://i.imgur.com/noJxTba.png"/>  

**Installation and configuration**  

1. Clone the repo.  
`git clone https://github.com/LegendaryLinux/slackroll.git`  

2. Install the two dependencies  
`cd slackroll && npm install -g strftime && npm install -g request`  

3. Create a new integration for your Slack. You'll want a new Slash Command. Four settings are important here.  
  a. **Command**: The command you specify to Slack to listen for a diceroll.  
  b. **URL**: Your server's endpoint. Ex: http://yourdomain.com:1500/ (assuming you use port 1500)  
  c. **Method**: This must be GET.  
  d. **Token**: Make note of this. You cna optionally use it for `slackToken` later.

4. Create a second integration for your Slack. This one is an incoming webhook. From there, you'll want to copy down the webhook url. The options here are mostly irrelevant, as slackRoll overrides them anyway. Just save the integration.

5. Take a look inside `slackRoll.js`. You'll find three configuration options. Set them accordingly:  
`serverPort`: The port you want the server to listen on  
`slackToken`: The token associated with your Slack integration (optional)  
`webhookUrl`: The webhook url associated with your Slack integration  

6. Once the integration is set up, just start the server.  
`node slackRoll.js`

That's it. Enjoy your dice!
