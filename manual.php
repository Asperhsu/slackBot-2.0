<?php
require('vendor/autoload.php');
date_default_timezone_set('Asia/Taipei');

?>
<!DOCTYPE html>
<html lang="">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>SlackBot Manual</title>
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
		<style>
			body{ padding:0 40px; line-height: 2em; }
			.form-control{ height: auto; line-height: 2em; }
		</style>
	</head>
	<body>
		<h1 class="text-center">Slack Bot簡易使用手冊</h1>
		
		<div class="container">
			<div class="row">
				<div class='col-sm-3 visible-md visible-lg'>
					<div class='well well-sm' id='sidebar' data-spy="affix" data-offset-top="0">
					<ul class="nav nav-pills nav-stacked">
						<li><a href="#section-1" >簡介</a></li>
						<li><a href="#section-2" >Incoming Webhooks</a></li>
						<li><a href="#section-3" >Outgoing Webhooks</a></li>
						<li><a href="#section-4" >此程式的使用說明</a></li>
					</ul>
					</div>
				</div>
				<div class='col-sm-9' >
					
					<div id="section-1">
						<div class='jumbotron'>
							<h1>簡介</h1>
							<p>
								利用Slack提供的兩個Webohook來設計機器人，可以依據設定的關鍵字回應一些資訊。以下是簡單的設定方法
							</p>
							<p>
								Incoming Webhooks是提供外部(指非Slack)程式發送訊息的服務，作為機器人回應訊息的方法；
								Outgoing Webhooks則是當slack偵測到設定的關鍵字時，則會發送該訊息的詳細資料到設定的外部網址；
								除可作為機器人外，也可作為訊息紀錄：如設定會議記錄關鍵字，則會把該訊息儲存並發送EMAIL之類的行為。
							</p>
						</div>
					</div>

					<hr>

					<div id="section-2">
						<h2>Incoming Webhooks</h2>
						<div class="form-control">
							<div class="row">
								<div class="col-sm-3" style="text-align: right;">
									<img src="/img/webhooks.png" style="height:8em">
								</div>

								<p class="col-sm-7">
									Incoming Webhooks 是可以讓外部的程式透過網址發送訊息至Slack內，也就是機器人回應的方法；<br/>
									其中的設定(channel, icon, Bot name)都可以利用傳送給 Slack 的 "payload" 做設定。
								</p>
							</div>
							<p class="text-info">
								請記得您的 Webhook URL，或知道怎麼回來查看
							</p>							
						</div>
						
						<ol>
							<li>在 <a href="https://slack.com/apps" target="_blank">Slack Apps</a> 裡搜尋 Incoming Webhooks</li>
							<li>選擇您的 Team</li>
							<li>點選 Add Configure</li>
							<li>Post to Channel 任選一個頻道(預設發送的頻道，之後都可以更改)，點選新增</li>
							<li>一些使用說明，其中最重要的是 Webhook URL，這是讓程式發送訊息的目的網址</li>
							<li>下方的 Integration Settings 除了 Webhook Url 外，只是提供預設值讓 BOT 發送時顯示</li>
						</ol>						
					</div>

					<hr>

					<div id="section-3">
						<h2>Outgoing Webhooks</h2>
						<div class="form-control">
							<div class="row">
								<div class="col-sm-3" style="text-align: right;">
									<img src="/img/webhooks.png" style="height:8em">
								</div>

								<p class="col-sm-7">
									Outgoing Webhooks 是當 Slack 偵測到所設定的關鍵字時，就會將該訊息詳細資料發送至所設定的外部網址。
								</p>								
							</div>
							<p class="text-info">
								請記得您的 Token，或知道怎麼回來查看
							</p>						
						</div>
						
						<ol>
							<li>在 <a href="https://slack.com/apps" target="_blank">Slack Apps</a> 裡搜尋 Outgoing Webhooks</li>
							<li>選擇您的 Team</li>
							<li>點選 Add Outoing Webhooks integration</li>
							<li>Outgoing Data 中詳述會發送的資料結構，以PHP來說，這些資料會存在 $_POST 內</li>
							<li>Integration Settings:
								<ul>
									<li>Channel: 設定BOT監聽的頻道，也可選擇ANY監聽所有頻道</li>
									<li>Trigger Word: 觸發的關鍵字，僅有在此設定的關鍵字才會觸發</li>
									<li>URL: 觸發後要傳送的外部連結</li>
									<li>Token: 專屬此Webhook的鑰匙，可用來檢查或過濾來源頻道</li>
								</ul>
							</li>
						</ol>						
					</div>

					<hr>

					<div id="section-4">
						<h2>此程式的使用說明</h2>
						<div class="form-control">
							此程式主要是由 SlackBot ,SlackBotServiceProvider, SlackBotable 組成<br/>
							SlackBot用來處理與Slack溝通的工具，已經包含Incoming and Outgoing的處理。<br/>
							SlackBotServiceProvider則是作為處理關鍵字的服務，註冊後會在關鍵字符合時觸發以SlackBotable實作的程式。
						</div>

						<h3>SlackBot 主要方法</h3>
						<ul>
							<li>
								setBotChannel, setBotIcon, setBotName: <br/>
								設定發送到Slack頻道時 BOT 的樣貌
							</li>
							<li>
								setCommandSplitString: <br/>
								設定關鍵字後方的參數切割方法(正規表示式)；<br/>
								如用戶輸入 "天氣 新北市"，程式會取得 "新北市" 這個參數<br/>
								可更改符合您訊息的參數方法
							</li>
							<li>
								您可直接像存取物件變數的方法存取Slack傳送的資訊，如：
								<pre>$slack = new SlackBot(); echo $slack->user_name;</pre>
							</li>
							<li>
								sendMsg, sendMsgforGAE:<br/>
								發送訊息到Slack的頻道；sendMsg是使用Curl發送，因GAE(Google App Engine)無法使用Curl，改使用sendMsgforGAE以stream context發送。
							</li>
						</ul>

						<h3>SlackBotServiceProvider 主要方法</h3>
						<ul>
							<li>
								register: <br/>
								接受字串或陣列，內容為實作 SlackBotable 的物件名稱。
							</li>
							<li>
								trigger: <br/>
								觸發監聽的程序，需傳入 SlackBot 的實體。
							</li>
						</ul>

						<h3>SlackBotable 主要介面</h3>
						<ul>
							<li>
								register: <br/>
								註冊監聽的關鍵字，回傳一陣列
							</li>
							<li>
								trigger: <br/>
								當關鍵字相符時，會呼叫此方法並傳入 SlackBot 的實體。
							</li>
						</ul>
											
					</div>
					
				</div>
			</div>		
		</div>
		
		<hr>

		<p style='text-align: center;'>
			Asper &copy; 2016
		</p>

		<script src="//code.jquery.com/jquery.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
		<script>
		$(function(){
			$("body").scrollspy({ target: '#sidebar', offset:70 });
			$("#sidebar a").click(function(){
				var target = this.hash,
				$target = $(target);
				$('html, body').stop().animate({
					'scrollTop': $target.offset().top-60
				}, 900, 'swing');
				return false;
			});
			// $(".container img").addClass('img-responsive');
		});
			
		</script>
	</body>
</html>

	