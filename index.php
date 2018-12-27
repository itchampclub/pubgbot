<?php
/**
 * Use for return easy answer.
 */
require_once('./vendor/autoload.php');
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
$channel_token = '8LSoXWYVTlV7oV82tKW6Rw9YdFLm/kcM4FzC2LACY+zpCP00zb012tMeG/NakCYCDP/y9aYS5nyZpW9vmFqihBSlQu0wn+fM9Z86qz9atTM6+KFcIZgPglsuGOfMGRaSGZ+Ur9r1DipRHh31MvR/3wdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'ad7b3270006ea092a56f1ad1b49d7a4c';
$content = file_get_contents('php://input');
$events = json_decode($content, true);


if (!is_null($events['events'])) {
	foreach ($events['events'] as $event) {
    
		if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
            $replyToken = $event['replyToken'];
            switch($event['message']['text']) {
                
                case 'tel':
                    $respMessage = '089-5124512';
            $httpClient = new CurlHTTPClient($channel_token);
            $bot = new LINEBot($httpClient, array('channelSecret' => $channel_secret));
            $textMessageBuilder = new TextMessageBuilder($respMessage);
            $response = $bot->replyMessage($replyToken, $textMessageBuilder);
                    break;
                case 'address':
                    $respMessage = '99/451 Muang Nonthaburi';
                    break;
                case 'boss':
                    $respMessage = '089-2541545';
                    break;
                case 'idcard':
                    $respMessage = '5845122451245';
                    break;
                case 'i':
                    $respMessage = 'Hello !!!!';
                    break;
                default:
                    $respMessage = 'Sorry sir...';
                    break;
            }                  
        }
	}
	echo "Reply";
}
else
{
$httpClient = new CurlHTTPClient($channel_token);
$bot = new LINEBot($httpClient, array('channelSecret' => $channel_secret));
$textMessageBuilder = new TextMessageBuilder('hello');
$response = $bot->pushMessage('Ub3ea97c513612d6e3401302f051f81dc', $textMessageBuilder);
	echo "Push";
}
echo "OK";
