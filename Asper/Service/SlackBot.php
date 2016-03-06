<?php

namespace Asper\Service;

class SlackBot {
	
	protected $token = null;
	protected $hookUrl = null;

	protected $triggerData = [];
	protected $commands = [];	

	protected $commandSplitString = "/[\s]+/";

	protected $botProfile = [
		'channel' => 'general',
		'icon' => ':slack:',
		'name' => 'BOT',
	];

	public function setBotChannel($channel){
		$this->botProfile['channel'] = $channel;
	}

	public function setBotIcon($icon){
		$icon = str_replace(':', '', $icon);
		$this->botProfile['icon'] = ':'.$icon.':';
	}

	public function setBotName($name){
		$this->botProfile['name'] = $name;
	}

	public function setCommandSplitString($string){
		$this->commandSplitString = $string;
	}
	
	protected function setToken($token){
		if( !is_null($token) ){
			$this->token = $token;
			return $this;
		}

		if( defined('SLACK_TOKEN') ){
			$this->token = constant('SLACK_TOKEN');
			return $this;
		}
	}

	protected function setHookUrl($hookUrl){
		if( !is_null($hookUrl) ){
			$this->hookUrl = $hookUrl;
			return $this;
		}

		if( defined('SLACK_HOOK_URL') ){
			$this->hookUrl = constant('SLACK_HOOK_URL');
			return $this;
		}

		if( is_null($this->hookUrl) ){
			throw new \Exception("Need Slack Hook Url. You can pass it from construct or define 'SLACK_HOOK_URL'. ");
		}
	}

	protected function setReplyChannel(){
		if( !strlen($this->triggerData['channel_name']) ){
			return $this;
		}

		return $this->setBotChannel($this->triggerData['channel_name']);
	}

	public function __construct($token=null, $hookUrl=null){
		$this->setToken($token);
		$this->setHookUrl($hookUrl);
		
		$this->parseTriggerData();
		
		$this->checkToken();

		$this->parseCommand();

		$this->setReplyChannel();
	}

	public function  __get($name){
		if(array_key_exists($name, $this->triggerData)) {
			return $this->triggerData[$name];
		}

		if($name == 'commands'){
			return $this->commands;
		}
		return null;
	}
	protected function parseTriggerData(){
		$this->triggerData = [
			'token'			=> isset($_POST['token']) ? $_POST['token'] : null,
			'team_id'		=> isset($_POST['team_id']) ? $_POST['team_id'] : null,
			'team_domain'	=> isset($_POST['team_domain']) ? $_POST['team_domain'] : null,
			'channel_id'	=> isset($_POST['channel_id']) ? $_POST['channel_id'] : null,
			'channel_name'	=> isset($_POST['channel_name']) ? $_POST['channel_name'] : null,
			'timestamp'		=> isset($_POST['timestamp']) ? $_POST['timestamp'] : null,
			'user_id'		=> isset($_POST['user_id']) ? $_POST['user_id'] : null,
			'user_name'		=> isset($_POST['user_name']) ? $_POST['user_name'] : null,
			'text'			=> isset($_POST['text']) ? $_POST['text'] : null,
			'trigger_word'	=> isset($_POST['trigger_word']) ? $_POST['trigger_word'] : null,
		];

		return $this;
	}

	protected function parseCommand(){
		$trigger_word = $this->triggerData['trigger_word'];
		$text = $this->triggerData['text'];
		$removeTriggerWord = trim(preg_replace('/'.$this->trigger_word.'/', '', $text, 1));

		if( strlen($removeTriggerWord) ){
			$this->commands = preg_split($this->commandSplitString, $removeTriggerWord);	
		}

		return $this;
	}

	protected function checkToken(){
		if( is_null($this->token) ){
			return true;
		}

		$token = $this->triggerData['token'];
		$isSame = strlen($token) && ($token == $this->token);

		if( !$isSame ){
			throw new \Exception("Token not match");
		}

		return $this;
	}

	protected function preparePayload($msg){
		$payLoad = array(
			'channel' 		=> '#'.$this->botProfile['channel'],
			'icon_emoji' 	=> $this->botProfile['icon'],
			'username' 		=> $this->botProfile['name'],
			'text' 			=> $msg
		);
		return 'payload='.json_encode($payLoad);
	}

	public function sendMsg($msg){
		$postData = $this->preparePayload($msg);

		$ch = curl_init($this->hookUrl);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	// send message for google app engine, 
	// gae can't use curl, file_get_contents instead
	public function sendMsgforGAE($msg){
		$postData = $this->preparePayload($msg);

		$context = [
			'http' => [
				'method' => 'POST',
				'header' => "Content-Length: ".strlen($postData)."\r\n",
				'content'=> $postData
			]
		];
		$context = stream_context_create($context);
		$result = file_get_contents($this->hookUrl, false, $context);

		return $result;
	}


}