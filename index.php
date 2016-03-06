<?php
require('vendor/autoload.php');
date_default_timezone_set('Asia/Taipei');

// define("SLACK_TOKEN", "YOUR TOKEN"); //optional
define("SLACK_HOOK_URL", "YOUR HOOK URL");

if( !count($_POST) ){
	header("location: /manual");
}

$slackServices = [
	Asper\Slackbot\Currency::class,	
	Asper\Slackbot\Weather::class,	
];
$serviceProvider = new Asper\Slackbot\SlackBotServiceProvider();
$serviceProvider->register($slackServices);

$slackBot = new Asper\Service\SlackBot();
$serviceProvider->trigger($slackBot);