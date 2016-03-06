<?php

namespace Asper\Contract;

use Asper\Service\SlackBot;

interface SlackBotable {
	
	public function register();

	public function trigger(SlackBot $slackBot);

}