<?php
require('vendor/autoload.php');

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

try{
	$slackBot = new Slackbot();
}catch(Exception $e){
	die('Oops! something Error!');
}

$slackBot->setName( $slackBot->trigger_word );
$trigWord = $slackBot->trigger_word;
$args = $slackBot->args;

//weather
if($trigWord == '天氣' || strtolower($trigWord) == 'weather'){
	$cmds = $args;
	$weather = new Weather();	

	$cmd = array_shift($cmds);
	var_dump($cmd);

	if(!strlen($cmd)){
		$msg = array();
		$msg[] = "Help: ";
		$msg[] = sprintf("天氣資訊=>  %s 城市名稱", $trigWord);
		$msg[] = sprintf("城市列表=>  %s 城市", $trigWord);
		
		$msg = implode("\n", $msg);
		echo $msg;
		$result = $slackBot->sendGAE($msg);
		exit;
	}

	if($cmd == '城市'){
		$msg = '城市列表: '.$weather->getCities();
		echo $msg;
		$result = $slackBot->sendGAE($msg);
		exit;
	}

	$city = $cmd;
	if( !$weather->isCityExist($city) ){
		$msg = "找不到有關 $city 的資料";
	}else{
		$msg = $weather->getWeather($city);
	}
	echo $msg;
	$result = $slackBot->sendGAE($msg);
	exit;
}

if($trigWord == '黑黑'){
	$msg = "我沒有錢 快窮死了";
	$cmd = implode(" ", $args);
	if(strlen($cmd)){
		$msg .= " (回頭按下購買 <".$cmd.">)";
	}
	echo $msg;
	$slackBot->setIcon(":guardsman:");
	$result = $slackBot->sendGAE($msg);
	exit;
}

if($trigWord == 'MACA'){
	$msg = '';
	$cmd = implode(" ", $args);
	
	if(strlen($cmd)){
		$msg .= $cmd;
	}

	$msg .= "<=好坑 不跳嗎?";
	echo $msg;
	
	$slackBot->setIcon(":monkey_face:");
	$result = $slackBot->sendGAE($msg);
	exit;
}

if($trigWord == '匯率'){
	$currencyList = array(
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

	
	$msg = array();
	$decide = decideMode($args);
	switch( $decide['mode'] ){
		case 'help':
			$cmd = implode(" ", $args);
			if( strlen($cmd) ){
				$msg[] = "您所輸入的 ".$cmd." 不正確\n";
			}
			$msg[] = " 查詢匯率說明:";
			$msg[] = "\t查詢匯率請使用: 匯率 幣別/幣名";
			$msg[] = "\t查詢匯率與台幣兌換請使用: 匯率 幣別/幣名 台幣";
			$msg[] = "\t查詢匯率與外幣兌換請使用: 匯率 台幣 幣別/幣名";
			$msg[] = "\t可用幣別為 ".implode(", ", array_keys($currencyList));
			$msg = implode("\n", $msg);
		break;
		case 'rates':
			$rates = $decide['rates'];
			$msg = getRateMessage($rates);
			$msg .= dataRef($decide['currency']);
		break;
		case 'change':
			$rates = $decide['rates'];
			$msg = getRateMessage($rates);
			$msg .= convRate($rates, $decide['amount']);
			$msg .= dataRef($decide['currency']);
		break;
		case 'reverse':
			$rates = $decide['rates'];
			$msg = getRateMessage($rates);
			$msg .= convRate($rates, $decide['amount'], true);
			$msg .= dataRef($decide['currency']);
		break;
	}

	echo $msg;	
	$slackBot->setIcon(":heavy_dollar_sign:");
	$result = $slackBot->sendGAE($msg);
	exit;

}
function decideMode($args){
	if( !strlen($args[0]) ){ return ['mode'=>'help']; }	
	if( $no = isNumber($args[0]) ){
		$rates = getRate($args[1]);
		if( $rates == false ){ return ['mode'=>'help']; }
		return ['mode'=>'reverse', 'amount'=>$no, 'currency'=> $args[1], 'rates'=>$rates];
	}

	$rates = getRate($args[0]);
	if( $rates == false ){ return ['mode'=>'help']; }		
	if( $no = isNumber($args[1]) ){ return ['mode'=>'change', 'currency'=> $args[0], 'amount'=>$no, 'rates'=>$rates]; }
	return ['mode'=>'rates', 'currency'=> $args[0], 'rates'=>$rates];
}
function isNumber($target){
	 $switched = str_replace(',', '', $target);
	 if(is_numeric($target)){
	 	return intval($target);
	 }elseif(is_numeric($switched)){
	 	return floatval($switched);
	 } else {
	 	return false;
	 }
}
function getRate($currency){
	global $currencyList;

	$pass = false;
	$currency = strtoupper($currency);
	if( isset($currencyList[$currency]) ){	//代號
		$pass = true;
	}
	if( in_array($currency, $currencyList) ){
		$currency = array_search($currency, $currencyList);	//中文名
		$pass = true;
	}
	if(!$pass){ return false; }

	$url = "http://asper-bot-rates.appspot.com/currency.json?".$currency;
	$json = file_get_contents($url);
	$data = json_decode($json, true);

	$data['currency'] = $currency;
	$data['currencyName'] = $currencyList[$currency];

	return $data;
}
function getRateMessage($rate){	
	
	$msg = array();
	$msg[] = sprintf("台幣 對 %s 匯率(更新時間:%s)", $rate['currency'], date("Y-m-d H:i:s", $rate['updateTime']) );
	$msg[] = sprintf("\t現金匯率>> 買入 %s, 賣出 %s", $rate['rates']['buyCash'], $rate['rates']['sellCash']);
	$msg[] = sprintf("\t即期匯率>> 買入 %s, 賣出 %s", $rate['rates']['buySpot'], $rate['rates']['sellSpot']);

	return implode("\n", $msg);
}

function convRate($rate, $amount, $reverse=false){
	$currency = $rate['currency'];
	$currencyName = $rate['currencyName'];
	$sellCash = $rate['rates']['sellCash'];

	if( $reverse ){
		$str = "$%s %s(%s)可兌換為 $%s 台幣(TWD)";
		$outMoney = $amount;
		$twd = round($amount*$sellCash, 3);
		$str = sprintf($str, number_format($outMoney), $currencyName, $currency, number_format($twd));
	}else{
		$str = "$%s 台幣(TWD)可兌換為 $%s %s(%s)";
		$outMoney = round($amount/$sellCash, 3);
		$twd = $amount;
		$str = sprintf($str, number_format($twd), number_format($outMoney), $currencyName, $currency);
	}
	$msg[] = '';
	$msg[] = '幣值換算>>';
	$msg[] = $str;

	return implode("\n", $msg);	
}

function dataRef($currency){
	return "\n\n<http://rate.bot.com.tw/Pages/Static/UIP003.zh-TW.htm| 資料來源: 台灣銀行> | ".
		   sprintf("<https://www.google.com/finance?q=TWD%s| 歷史紀錄: Google Finance>", $currency);
}