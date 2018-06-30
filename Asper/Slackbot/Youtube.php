<?php

namespace Asper\SlackBot;

use Asper\Contract\SlackBotable;
use Asper\Service\SlackBot;

class Youtube implements SlackBotable{

	protected $entryPoint = "https://www.googleapis.com/youtube/v3/search";
	protected $apiKey;
	protected $maxItems = 10;

	public function __construct(){
		$this->apiKey = getenv('YOUTUBE_API_KEY');
	}

	public function register(){
		return ['youtube', '點歌'];
	}

	public function trigger(SlackBot $slackBot){
		$slackBot->setBotName( $slackBot->trigger_word );
		$slackBot->setBotIcon(":musical_note:");
		$commands = $slackBot->commands;

		// filter additional parameter
		$parameter = [];
		$pattern = '/--(\w+)/';
		foreach($commands as $key => $command){
			$matches = [];
			if( preg_match($pattern, $command, $matches) ){
				$parameter[] = $matches[1];
				unset($commands[$key]);
			}
		}

		$q = implode('+', $commands);

		if( !strlen($q) ){
			$msg = " 點歌使用說明: youtube|點歌 `關鍵字`";
			return $slackBot->sendMsgforGAE($msg);
		}

		//check user
		$userID = $slackBot->user_id;
		if( is_numeric($q) AND $this->isUserSearched($userID) ){
			$items = $this->getUserLastSearch($userID);
			$item = $items[ ($q - 1) ];

			$msg = $this->youtubeVideoLink($item);
			return $slackBot->sendMsgforGAE($msg);
		}

		if( in_array('first', $parameter) ){
			$msg = $this->searchFirstOne($q);
			return $slackBot->sendMsgforGAE($msg);
		}

		$msg = $this->search($q, $userID);
		return $slackBot->sendMsgforGAE($msg);
	}

	protected function getResponse($q){
		$url = $this->bulidQueryPath($q);

		$response = file_get_contents($url);
		$response = json_decode($response, true);

		return $response;
	}

	protected function searchFirstOne($q){
		$response = $this->getResponse($q);

		$items = $response['items'];

		if( !count($items) ){
			return sprintf("%s Not Found", $q);
		}

		return $this->youtubeVideoLink(array_shift($items));
	}

	protected function search($q, $userID){
		$response = $this->getResponse($q);

		if( isset($response['error']) ){
			return $this->errorHandler($response);
		}

		$this->saveUserLastSearch($userID, $response['items']);

		return $this->itemsProcesser($q, $response);
	}

	protected function bulidQueryPath($q, Array $options = []){
		$preDefinedOptions = [
			'part' => 'snippet',
			'type' => 'video',
			'q'	=> $q,
			'key' => $this->apiKey,
			'maxResults' => $this->maxItems,
		];

		$options = array_merge($preDefinedOptions, $options);

		$url = $this->entryPoint.'?'.http_build_query($options);
		return $url;
	}

	protected function itemsProcesser($q, Array $response){
		$items = $response['items'];



		if( !count($items) ){
			return sprintf("%s Not Found", $q);
		}

		if( count($items) == 1 ){
			return $this->youtubeVideoLink(array_shift($items));
		}
		return $this->generateLists($q, $items);
	}

	protected function generateLists($q, Array $items){
		$msg = [];
		$msg[] = sprintf('*Search `%s`*', $q);

		$recordsCnt = count($items) > $this->maxItems ? $this->maxItems : count($items);

		for($i=0; $i<$recordsCnt; $i++){
			$item = $items[$i];
			$msg[] = sprintf("%02d: %s", $i+1, $this->youtubeVideoLink($item));
		}

		$msg[] = "請繼續以 點歌|youtube `號碼`";

		return implode("\n", $msg);
	}

	protected  function errorHandler(Array $response){
		return sprintf("Error %s: %s", $response['code'], $response['message']);
	}

	protected function youtubeVideoLink(Array $item){
		$videoId = $item['id']['videoId'];
		$title = $item['snippet']['title'];
		return sprintf("<https://www.youtube.com/watch?v=%s| %s>", $videoId, $title);
	}

	protected function getUserLastSearchKey($userID){
		return 'slackYoutubeResults-'.$userID;
	}

	protected function saveUserLastSearch($userID, Array $items){
		if( !strlen($userID) OR !count($items) ){
			return $this;
		}
		echo 'saveUserLastSearch '.$userID;

		$cache = new \MemCache();
		$key = $this->getUserLastSearchKey($userID);
		return $cache->set($key, json_encode($items), 300);
	}

	protected function getUserLastSearch($userID){
		if( !strlen($userID) ){
			return $this;
		}

		$cache = new \MemCache();
		$key = $this->getUserLastSearchKey($userID);
		$results = $cache->get($key);
		return json_decode($results, true);
	}

	protected function isUserSearched($userID){
		$cache = new \MemCache();
		$key = $this->getUserLastSearchKey($userID);
		$results = $cache->get($key);
		return (bool)$results;
	}

}