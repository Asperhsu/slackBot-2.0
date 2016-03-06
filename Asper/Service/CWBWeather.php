<?php

namespace Asper\Service;

class CWBWeather {
	protected $chineseWeek = array('日','一','二','三','四','五','六');
	protected $CWBUrl = 'http://opendata.cwb.gov.tw/opendata/MFC/F-C0032-005.xml';	
	protected $cityList = [
		'台北市',
		'新北市',
		'桃園縣',
		'台中市',
		'台南市',
		'高雄市',
		'基隆縣',
		'新竹縣',
		'新竹市',
		'苗栗縣',
		'彰化縣',
		'南投縣',
		'雲林縣',
		'嘉義縣',
		'嘉義市',
		'屏東縣',
		'宜蘭縣',
		'花蓮縣',
		'台東縣',
		'澎湖縣',
		'金門縣',
		'連江縣'
	];

	public function __construct(){	

	}

	public function getCities(){
		return $this->cityList;
	}	
	
	/**
	 * fetch Weekly forecasting, accept city parameter to return particular
	 * @param  string $city
	 * @return array  forecasting data
	 */
	public function fetchWeatherWeekly(){
	    $url = "http://opendata.cwb.gov.tw/opendata/MFC/F-C0032-005.xml"; // 7days
	    $forecasting = $this->fetchWeather($url);
	    
	    return $forecasting;
	}

	/**
	 *  fetch weather from cwb opendata http://opendata.cwb.gov.tw
	 *  @param url: opendata xml url
	 *  @return array cities weather data
	 *
	 */
	public function fetchWeather($url){
		if (!strlen($url)) {
			return [];
		}
		$xmlstring = file_get_contents($url);
		$forecasting = $this->xml2array($xmlstring);
		$cities = [];
		foreach ($forecasting['dataset']['location'] as $city) {
			$cityName = $city['locationName'];
			$cityName = str_replace('臺', '台', $cityName);
			$element = $city['weatherElement'];
			$cities[$cityName] = $this->parseCityForecastData($element);
		}
		return $cities;
	}

	/**
	 * parse city forecasting element
	 * @param  array  $elements from opendata xml weatherElement data
	 * @return array  associate with reformated date, hour and element name(weather data type)
	 */
	protected function parseCityForecastData(array $elements){
		$weather = [];
		foreach ($elements as $element) {
			$elementName = strtolower($element['elementName']);
			foreach ($element['time'] as $time) {
				$foo = $this->reformatTime($time['startTime']);
				$sDate = $foo['date'];
				$sHour = $foo['hour'];
				$foo = $this->reformatTime($time['endTime']);
				$eDate = $foo['date'];
				$eHour = $foo['hour'];
				$parameterName = (string) $time['parameter']['parameterName'];
				$weather[$sDate][$sHour][$elementName] = $parameterName;
				$weather[$eDate][$eHour][$elementName] = $parameterName;
			}
		}
		return $weather;
	}

	/**
	 * reformat time string to desite format
	 * @param  string $timeStr time string
	 * @return array  with date and hour data
	 */
	protected function reformatTime($timeStr){
		$time = strtotime($timeStr);
		$weekName = $this->chineseWeek[date('w', $time)];
		$date = date('m-d', $time) . '(' . $weekName . ')';
		$hour = date('H', $time);
		return compact('date', 'hour');
	}

	/**
	 * convert xml string to array using simpleXML parser and json (de)encoder
	 * @param  string $xmlstring xml string
	 * @return array
	 */
	protected function xml2array($xmlstring){
		$xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
		$jsonData = json_encode($xml);
		return json_decode($jsonData, true);
	}

}