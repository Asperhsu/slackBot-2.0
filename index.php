<?php
require('vendor/autoload.php');
date_default_timezone_set('Asia/Taipei');
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

if (getenv('DEBUG') === "true") {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

define("SLACK_TOKEN", getenv('SLACK_TOKEN'));
define("SLACK_HOOK_URL", getenv('SLACK_HOOK_URL'));

if (!count($_POST)) {
    header("location: /manual");
}

$slackServices = [
    Asper\Slackbot\Currency::class,
    Asper\Slackbot\Weather::class,
    Asper\Slackbot\Youtube::class,
];
$serviceProvider = new Asper\Slackbot\SlackBotServiceProvider();
$serviceProvider->register($slackServices);

$slackBot = new Asper\Service\SlackBot();
$serviceProvider->trigger($slackBot);
