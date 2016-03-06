<?php

namespace Asper\SlackBot;

use Asper\Contract\Cacheable;
use Asper\Contract\SlackBotable;
use Asper\Service\CWBWeather;
use Asper\Service\SlackBot;
use Asper\Util\MemCache;

class Weather implements SlackBotable{
	protected $cache;
	protected $cacheExpireSec = 600;

	protected $Weather;
	protected $weatherData;

	protected $iconMapping = [
		'多雲' => ':cloud:',
		'多雲時晴' => ':barely_sunny:',
		'多雲短暫雨' => ':partly_sunny_rain:',		
		'多雲短暫陣雨' => ':partly_sunny_rain:',
		'陰時多雲短暫陣雨' => ':rain_cloud:',
		'陰時多雲短暫雨' => ':rain_cloud:',
		'陰時多雲' => ':cloud:',
		'晴時多雲' => ':mostly_sunny:',
		'陰短暫陣雨或雷雨' => ':thunder_cloud_and_rain:',
		'多雲短暫陣雨或雷雨' => ':thunder_cloud_and_rain:',
		'陰陣雨' => ':rain_cloud:',
		'陰有雨' => ':rain_cloud:',
	];

	public function __construct(){		
		$this->cache = new MemCache;

		$this->Weather = new CWBWeather();
	}

	public function register(){
		return ['天氣', 'weather'];
	}

	public function trigger(SlackBot $slackBot){
		$slackBot->setBotName('天氣');
		$slackBot->setBotIcon(":earth_asia:");
		$commands = $slackBot->commands;

		$msg = $this->responseFactory($commands);
		return $slackBot->sendMsgforGAE($msg);
	}

	protected function isListCity($commands){
		if( !isset($commands[0]) ){ return false; }

		return $commands[0] == '城市';
	}

	protected function isCityWeatherExist($cityName){
		$this->fetchWeather();
		$cityName = str_replace('臺', '台', $cityName);
		return isset($this->weatherData[$cityName]);
	}

	protected function fetchWeather(){
		if( is_null($this->weatherData) ){
			$this->weatherData = $this->getWeather();
		}
		return $this->weatherData;
	}

	protected function responseFactory(Array $commands){
		if( !count($commands) ){ return $this->responseHelp(); }

		if( $this->isListCity($commands) ){
			return $this->responseCityList($commands);
		}
		
		return $this->responseCityWeather($commands);
	}

	protected function responseCityWeather($commands){
		$cityName = $commands[0];

		if( !$this->isCityWeatherExist($cityName) ){
			return "找不到有關 $cityName 的資料";
		}

		$msg = [ $cityName." 一周天氣預報：" ];
		foreach( $this->weatherData[$cityName] as $date => $hours){
			$msg[] = "\t".$date.': ';
			foreach($hours as $hour => $item){
				$msg[] = sprintf("\t\t%s時 %s%s (%s ~ %s)  ", 
							$hour,
							$this->iconMapping[$item['wx']],
							$item['wx'], 
							$item['maxt'],
							$item['mint']
						);
			}
		}
		return implode("\n", $msg);
	}

	protected function responseCityList(Array $commands){
		$cities = $this->Weather->getCities();

		return '城市列表: ' . implode(", ", $cities);
	}

	protected function responseHelp(){		
		$msg = [
			"Help: ",
			"天氣資訊=>  天氣 城市名稱",
			"城市列表=>  天氣 城市",
		];
		return implode("\n", $msg);
	}

	protected function getWeather($cacheExpireSec=null){
		if( is_null($this->cache) ){
			return $this->Weather->fetchWeatherWeekly();
		}

		// using cache
		$cacheExpireSec = $cacheExpireSec ?: $this->cacheExpireSec;
		$createTime = intval($this->cache->get('createTime'));
		$isExpired = ($createTime + $cacheExpireSec) < time();
		
		if( $isExpired ){
			$data = $this->Weather->fetchWeatherWeekly();
			$this->saveToCache($data, $cacheExpireSec);
			return $data;
		}
		return $this->loadFromCache();
	}

	protected function saveToCache($data, $cacheExpireSec){
		$weatherJson = json_encode($data);
		$this->cache->set('weather', $weatherJson, $cacheExpireSec);

		$this->cache->set('createTime', time(), $cacheExpireSec);
	}

	protected function loadFromCache(){
		$createTime = $this->cache->get('createTime');
		$weatherJson = $this->cache->get('weather');
		return json_decode($weatherJson, true);
	}


}