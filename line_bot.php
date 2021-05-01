<?php
require('vendor/autoload.php');

use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot;

// このphpファイルで使用する定数やメインのオブジェクトの定義
const channel_access_token = 'xxxxxxxxxxxxxxxxxx';
const channel_secret = 'xxxxxxxxxxxxxxxxxxxx';
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(channel_access_token);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => channel_secret]);


// ▼▼メイン処理▼▼
$resflg = resMassage($http_client, $bot);
if($resflg === false){
   $text = getTodayInfo();
   $genText = textMessageBuilder($text);
   sendLineMessage($genText, $httpClient, $bot);
} else {
    exit;
}
// ▲▲メイン処理▲▲

/**
 * メインのアクション。ユーザからのレスポンスかcronによる定期実行を切り分ける。
 * cron実行の場合は$http_request_bodyがNULLになるのでそこでf¥$resflgがfalseで返る
 *
 * @param object $http_client
 * @param object $bot Message
 * @return bool  $resflg 応答の有無のフラグ
 */
function resMassage($http_client, $bot){
    $signature = $_SERVER['HTTP_' . HTTPHeader::LINE_SIGNATURE];
    $http_request_body = file_get_contents('php://input');
    // cron実行の場合はリターンさせる。
    if(!$http_request_body){
        $resflg = false;
        return $resflg;
    }
    $events = $bot->parseEventRequest($http_request_body, $signature);
    $event = $events[0];
    
    $Receive_text = $event->getText();
    $reply_token = $event->getReplyToken();

    if($Receive_text === '今日は何の日？'){
        //LINEBOTの応答
        $text = getTodayInfo();
        $bot->replyText($reply_token, $text);
        $resflg = true;
        return;
    } elseif(isset($Receive_text) && !($Receive_text === '今日は何の日？')) {
        //今日は何の日と聞かれなかったとき
        $text = '返信ありがとうございます。' ."\n".'よければ「今日は何の日？」と聞いてみてください。';
        $bot->replyText($reply_token, $text);
        $resflg = true;
        return;
    } else{
        //例外
        $resflg = false;
        return $resflg;
    }
}

/**
 * 生成されたメッセージをBOTの友達全員に送付する。
 *
 * @param string $sendMessage
 * @param object $httpClient
 * @param object $bot
 */
function sendLineMessage ($sendMessage, $httpClient, $bot){
    $response = $bot->broadcast($sendMessage);
    error_log(date('Y/m/d H:i:s') .'：'.'[HTTPS_status：' . $response->getHTTPStatus() . ']'."\n", 3, './debug.log');
}

/**
 * 送信するテキストをTextMessageBuilderクラスから生成する。
 *
 * @param string $lineText
 * @return object $textMessageBuilder
 */
function textMessageBuilder ($lineText) {
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($lineText);
    return $textMessageBuilder;
}

/**
 * 日本記念日協会から今日の記念日をスクレイピングする。
 *
 * @return string $sendText
 */
function getTodayInfo() {
    require_once("./phpQuery-onefile.php");

    $contents = file_get_contents("https://www.kinenbi.gr.jp/");
    $html = phpQuery::newDocument($contents);

    $todayInfo = phpQuery::newDocument($html)->find(".today_kinenbilist font")->text();
    $sendText = date("m月d日") . 'には以下の記念日が登録されています。'  ."\n" . $todayInfo ."\n" . '参照サイト(一般社団法人日本記念日協会)' ."\n" .'https://www.kinenbi.gr.jp/' ;

    return $sendText;
}


