<?php

namespace Asper\SlackBot;

use Asper\Contract\SlackBotable;
use Asper\Service\SlackBot;

class Currency implements SlackBotable
{
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

    public function __construct()
    {
    }

    public function register()
    {
        return ['匯率'];
    }

    public function trigger(SlackBot $slackBot)
    {
        $slackBot->setBotName($slackBot->trigger_word);
        $slackBot->setBotIcon(":heavy_dollar_sign:");
        $commands = $slackBot->commands;

        $msg = $this->responseFactory($commands);
        return $slackBot->sendMsgforGAE($msg);
    }

    protected function isNumber($target)
    {
        $switched = str_replace(',', '', $target);
        if (is_numeric($target)) {
            return floatval($target);
        } elseif (is_numeric($switched)) {
            return floatval($switched);
        } else {
            return false;
        }
    }

    protected function isCurrency($currency)
    {
        $currency = strtoupper($currency);

        if (isset($this->currencyList[$currency])) {	//代號
            return $currency;
        }

        if (in_array($currency, $this->currencyList)) {
            $currency = array_search($currency, $this->currencyList);	//中文名
            return $currency;
        }

        return false;
    }

    protected function isCurrencyReverseTransform(array $commands)
    {
        if (!isset($commands[0])) {
            return false;
        }
        if (!isset($commands[1])) {
            return false;
        }

        $iso3 = $this->isCurrency($commands[1]);
        if ($iso3 == false) {
            return false;
        }

        $amount = $this->isNumber($commands[0]);
        if ($amount == false) {
            return false;
        }

        return [
            'iso3' => $iso3,
            'amount' => $amount,
        ];
    }

    protected function isCurrencyTransform(array $commands)
    {
        if (!isset($commands[0])) {
            return false;
        }
        if (!isset($commands[1])) {
            return false;
        }

        $iso3 = $this->isCurrency($commands[0]);
        if ($iso3 == false) {
            return false;
        }

        $amount = $this->isNumber($commands[1]);
        if ($amount == false) {
            return false;
        }

        return [
            'iso3' => $iso3,
            'amount' => $amount,
        ];
    }

    protected function isRateDisplay(array $commands)
    {
        if (!isset($commands[0])) {
            return false;
        }

        $iso3 = $this->isCurrency($commands[0]);
        if ($iso3 == false) {
            return false;
        }

        return $iso3;
    }

    protected function fetchRate($currency)
    {
        if (!$this->isCurrency($currency)) {
            return [];
        }

        try {
            $url = "https://tw.rter.info/capi.php";
            $json = file_get_contents($url);
            $data = json_decode($json, true);

            // CURRENCY -> USD -> TWD
            $USDTWD = $data['USDTWD']['Exrate'];
            $USDJPY = $data['USDJPY']['Exrate'];
            $updateTime = strtotime($data['USDJPY']['UTC']." UTC");
            $rate = 1 / $USDJPY * $USDTWD;
        } catch (\Exception $e) {
            $updateTime = $rate = null;
        }

        $response = [
            'iso3' => $currency,
            'currency' => $this->currencyList[$currency],
            'updateTime' => $updateTime,
            'rate' => $rate,
        ];

        return $response;
    }

    protected function responseFactory(array $commands)
    {
        if (!count($commands)) {
            return $this->responseHelp();
        }

        if ($data = $this->isCurrencyReverseTransform($commands)) {
            return $this->responseCurrencyReverseTransform($data);
        }

        if ($data = $this->isCurrencyTransform($commands)) {
            return $this->responseCurrencyTransform($data);
        }

        if ($iso3 = $this->isRateDisplay($commands)) {
            return $this->responseRate($iso3);
        }

        return $this->responseHelp();
    }

    protected function responseHelp()
    {
        $currency = implode(", ", array_keys($this->currencyList));
        $msg = [
            "*查詢匯率說明*",
            "\t查詢匯率請使用: 匯率 `幣別/幣名`",
            "\t查詢匯率與台幣兌換請使用: 匯率 `幣別/幣名` `台幣`",
            "\t查詢匯率與外幣兌換請使用: 匯率 `台幣` `幣別/幣名`",
            "\t可用幣別為 ".$currency,
        ];
        return implode("\n", $msg);
    }

    protected function responseCurrencyReverseTransform(array $data)
    {
        $msg = [];

        $rate = $this->fetchRate($data['iso3']);
        $msg[] = $this->rateMessage($rate);
        $msg[] = $this->currencyTransformMessage($rate, $data['amount'], true);
        $msg[] = $this->dataSourceMessage($data['iso3']);

        return implode("\n", $msg);
    }

    protected function responseCurrencyTransform(array $data)
    {
        $msg = [];

        $rate = $this->fetchRate($data['iso3']);
        $msg[] = $this->rateMessage($rate);
        $msg[] = $this->currencyTransformMessage($rate, $data['amount']);
        $msg[] = $this->dataSourceMessage($data['iso3']);

        return implode("\n", $msg);
    }

    protected function responseRate($iso3)
    {
        $msg = [];

        $rate = $this->fetchRate($iso3);
        $msg[] = $this->rateMessage($rate);
        $msg[] = $this->dataSourceMessage($iso3);

        return implode("\n", $msg);
    }

    protected function rateMessage(array $data)
    {
        $iso3 = $data['iso3'];
        $rate = $data['rate'];

        $dt = new \DateTime();
        $dt->setTimezone(new \DateTimeZone('Asia/Taipei'));
        $dt->setTimestamp($data['updateTime']);
        $updateTime = $dt->format('Y-m-d H:i:s');

        $msg = [
            sprintf("台幣對 *%s* 匯率(更新時間:%s)", $iso3, $updateTime),
            sprintf("\t`匯率` %s", $rate),
        ];
        return implode("\n", $msg);
    }

    protected function currencyTransformMessage(array $rate, $amount, $reverse=false)
    {
        $iso3 = $rate['iso3'];
        $currency = $rate['currency'];
        $rate = $rate['rate'];

        if ($reverse) {
            $str = "`$%s` %s(%s)可兌換為 `$%s` 台幣(TWD)";
            $outMoney = number_format($amount, 2);
            $twd = number_format(round($amount * $rate, 3), 2);
            $msg = sprintf($str, $outMoney, $currency, $iso3, $twd);
        } else {
            $str = "`$%s` 台幣(TWD)可兌換為 `$%s` %s(%s)";
            $outMoney = number_format(round($amount / $rate, 3), 2);
            $twd = number_format($amount, 2);
            $msg = sprintf($str, $twd, $outMoney, $currency, $iso3);
        }

        return implode("\n", [
            '', '*幣值換算*', $msg
        ]);
    }

    protected function dataSourceMessage($iso3)
    {
        return implode("", [
            "\n\n",
            sprintf("<https://tw.rter.info/currency/%s| 歷史紀錄: 即匯站>", $iso3),
            sprintf("<https://www.google.com/finance?q=TWD%s| 歷史紀錄: Google Finance>", $iso3)
        ]);
    }
}
