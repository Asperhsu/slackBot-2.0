<?php

namespace Asper\SlackBot;

use Asper\Contract\SlackBotable;
use Asper\Service\SlackBot;

class Currency implements SlackBotable{

	protected $currencyList = array(
		'USD' => '美元',
		'HKD' => '港幣',
		'GBP' => '英鎊',
		'AUD' => '澳幣',
		'CAD' => '加拿大幣',
		'SGD' => '新加坡幣',		
		'CHF' => '瑞士法郎',
		'JPY' => '日圓',
		'ZAR' => '南非幣',
		'SEK' => '瑞典克朗',
		'NZD' => '紐西蘭幣',
		'THB' => '泰銖',
		'PHP' => '菲律賓披索',
		'IDR' => '印尼盾',
		'EUR' => '歐元',
		'KRW' => '菲律賓披索',
		'VND' => '越南幣',
		'MYR' => '馬來西亞幣',
		'CNY' => '人民幣',		
	);

	public function __construct(){
		
	}

	public function register(){
		return ['匯率'];
	}

	public function trigger(SlackBot $slackBot){
		$slackBot->setBotName( $slackBot->trigger_word );
		$slackBot->setBotIcon(":heavy_dollar_sign:");
		$commands = $slackBot->commands;

		$msg = $this->responseFactory($commands);
		return $slackBot->sendMsgforGAE($msg);
	}

	protected function isNumber($target){
		$switched = str_replace(',', '', $target);
		if(is_numeric($target)){
			return intval($target);
		}elseif(is_numeric($switched)){
			return floatval($switched);
		} else {
			return false;
		}
	}

	protected function isCurrency($currency){
		$currency = strtoupper($currency);

		if( isset($this->currencyList[$currency]) ){	//代號
			return $currency;
		}

		if( in_array($currency, $this->currencyList) ){
			$currency = array_search($currency, $this->currencyList);	//中文名
			return $currency;
		}

		return false;
	}

	protected function isCurrencyReverseTransform(Array $commands){
		if( !isset($commands[0]) ){ return false; }
		if( !isset($commands[1]) ){ return false; }

		$iso3 = $this->isCurrency($commands[1]);
		if( $iso3 == false ){ return false; }

		$amount = $this->isNumber($commands[0]);
		if( $amount == false ){ return false; }

		return [
			'iso3' => $iso3,
			'amount' => $amount,
		];
	}

	protected function isCurrencyTransform(Array $commands){
		if( !isset($commands[0]) ){ return false; }
		if( !isset($commands[1]) ){ return false; }

		$iso3 = $this->isCurrency($commands[0]);
		if( $iso3 == false ){ return false; }

		$amount = $this->isNumber($commands[1]);
		if( $amount == false ){ return false; }

		return [
			'iso3' => $iso3,
			'amount' => $amount,
		];
	}	

	protected function isRateDisplay(Array $commands){
		if( !isset($commands[0]) ){ return false; }

		$iso3 = $this->isCurrency($commands[0]);
		if( $iso3 == false ){ return false; }

		return $iso3;
	}

	protected function fetchRate($currency){
		if( !$this->isCurrency($currency) ){ return []; }

		$url = "http://rate.asper.tw/currency.json?".$currency;
		$json = file_get_contents($url);
		$data = json_decode($json, true);
		$data['iso3'] = $currency;
		$data['currency'] = $this->currencyList[$currency];

		return $data;
	}

	protected function responseFactory(Array $commands){
		if( !count($commands) ){ return $this->responseHelp(); }		

		if( $data = $this->isCurrencyReverseTransform($commands) ){
			return $this->responseCurrencyReverseTransform($data);
		}

		if( $data = $this->isCurrencyTransform($commands) ){
			return $this->responseCurrencyTransform($data);
		}

		if( $iso3 = $this->isRateDisplay($commands) ){
			return $this->responseRate($iso3);
		}

		return $this->responseHelp();
	}

	protected function responseHelp(){
		$currency = implode(", ", array_keys($this->currencyList) );
		$msg = [
			" 查詢匯率說明:",
			"\t查詢匯率請使用: 匯率 幣別/幣名",
			"\t查詢匯率與台幣兌換請使用: 匯率 幣別/幣名 台幣",
			"\t查詢匯率與外幣兌換請使用: 匯率 台幣 幣別/幣名",
			"\t可用幣別為 ".$currency,
		];
		return implode("\n", $msg);
	}

	protected function responseCurrencyReverseTransform(Array $data){
		$msg = [];
		
		$rate = $this->fetchRate($data['iso3']);
		$msg[] = $this->rateMessage($rate);
		$msg[] = $this->currencyTransformMessage($rate, $data['amount'], true);
		$msg[] = $this->dataSourceMessage($data['iso3']);

		return implode("\n", $msg);
	}

	protected function responseCurrencyTransform(Array $data){		
		$msg = [];
		
		$rate = $this->fetchRate($data['iso3']);
		$msg[] = $this->rateMessage($rate);
		$msg[] = $this->currencyTransformMessage($rate, $data['amount']);
		$msg[] = $this->dataSourceMessage($data['iso3']);

		return implode("\n", $msg);
	}

	protected function responseRate($iso3){
		$msg = [];

		$rate = $this->fetchRate($iso3);
		$msg[] = $this->rateMessage($rate);
		$msg[] = $this->dataSourceMessage($iso3);

		return implode("\n", $msg);
	}

	protected function rateMessage(Array $rate){
		$iso3 = $rate['iso3'];
		$updateTime = date("Y-m-d H:i:s", $rate['updateTime']);
		$rates = $rate['rates'];

		$msg = [
			sprintf("台幣 對 %s 匯率(更新時間:%s)", $iso3, $updateTime),
			sprintf("\t現金匯率>> 買入 %s, 賣出 %s", $rates['buyCash'], $rates['sellCash']),
			sprintf("\t即期匯率>> 買入 %s, 賣出 %s", $rates['buySpot'], $rates['sellSpot']),
		];
		return implode("\n", $msg);
	}

	protected function currencyTransformMessage(Array $rate, $amount, $reverse=false){
		$iso3 = $rate['iso3'];
		$currency = $rate['currency'];
		$sellCash = $rate['rates']['sellCash'];

		if( $reverse ){
			$str = "$%s %s(%s)可兌換為 $%s 台幣(TWD)";
			$outMoney = number_format($amount);
			$twd = number_format(round($amount * $sellCash, 3));
			$msg = sprintf($str, $outMoney, $currency, $iso3, $twd);
		}else{
			$str = "$%s 台幣(TWD)可兌換為 $%s %s(%s)";
			$outMoney = number_format(round($amount / $sellCash, 3));
			$twd = number_format($amount);
			$msg = sprintf($str, $twd, $outMoney, $currency, $iso3);	
		}

		return implode("\n", [
			'', '幣值換算>>', $msg
		]);
	}

	protected function dataSourceMessage($iso3){
		return "\n\n<http://rate.bot.com.tw/Pages/Static/UIP003.zh-TW.htm| 資料來源: 台灣銀行> | ".
		   sprintf("<https://www.google.com/finance?q=TWD%s| 歷史紀錄: Google Finance>", $iso3);
	}

}