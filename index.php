<?php
/**
 * Created by PhpStorm.
 * User: luqman
 * Date: 2/25/17
 * Time: 1:33 PM
 */

use LINE\LINEBot\SignatureValidator;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

require 'vendor/autoload.php';

spl_autoload_register(function ($class_name){
    include  $class_name.'.php';
});

// load config
try{
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
}catch (Exception $e){
}

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$app = new Slim\App(['settings' => $config]);
$container = $app->getContainer();

$app->get('/', function (Request $request, Response $response){

});

$app->get('/profile/{id}', function (Request $request, Response $response, $args){
    $access_token = getenv('CHANNEL_ACCESS_TOKEN');
    $secret = getenv('CHANNEL_SECRET');
    $pass_signature = getenv('PASS_SIGNATURE');

    $http_client = new CurlHTTPClient($access_token);
    $bot = new LINEBot($http_client,['channelSecret' => $secret]);

    $profile = $bot->getProfile($args['id']);

    return print("<pre>".print_r($profile->getJSONDecodedBody(),1)."</pre>");
});

$app->post('/', function (Request $request, Response $response){

    $access_token = getenv('CHANNEL_ACCESS_TOKEN');
    $secret = getenv('CHANNEL_SECRET');
    $pass_signature = getenv('PASS_SIGNATURE');

    // get request body and line signature header
    $body 	   = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

    // log body and signature
    file_put_contents('php://stderr', 'Body: '.$body);

    // is LINE_SIGNATURE exists in request header?
    if (empty($signature)){
        return $response->withStatus(400, 'Signature not set');
    }
    if($pass_signature == 'false' && ! SignatureValidator::validateSignature($body,$secret, $signature)){
        return $response->withStatus(400, 'Invalid Signature');
    }

    $http_client = new CurlHTTPClient($access_token);
    $bot = new LINEBot($http_client,['channelSecret' => $secret]);

    $data = json_decode($body,true);
    foreach ($data['events'] as $event) {
        if(! isset($event['source']['userId'])) continue;

        $user_id = $event['source']['userId'];

        if($event['type'] == 'follow'){
            if(User::exist($user_id)){
                $user = User::findOne(['user_id' => $user_id]);
                $bot->pushMessage($user_id, new TextMessageBuilder("Welcome back,{$user->display_name} :)"));
                $bot->pushMessage($user_id, new StickerMessageBuilder(1, 4));
                $bot->pushMessage($user_id, BotHelper::getMenu());

            }else{
                $profile = $bot->getProfile($user_id)->getJSONDecodedBody();
                try{

                    $user = new User();
                    $user->user_id = $user_id;
                    $user->display_name = $profile['displayName'];
                    $user->insert();
                    $bot->pushMessage($user_id, new LINEBot\MessageBuilder\TextMessageBuilder("Hi {$user->display_name}, welcome to Talk To Stranger Bot!"));
                    $bot->pushMessage($user_id, new StickerMessageBuilder(1, 13));
                    $bot->pushMessage($user_id, new TextMessageBuilder('Here, you can talk to stranger around the world anonymously'));
                    $bot->pushMessage($user_id, new TextMessageBuilder('Each session of the talk will only let you send 30 messages, so make sure you only say something nice to the person on the other end :)'));
                    $bot->pushMessage($user_id, new TextMessageBuilder('The talk session will end automatically when one of you reach your 30 message quota.'));
                    $bot->pushMessage($user_id, new TextMessageBuilder('If you want to end the session manually, you can type "menu", and select "End talk".'));
                    $bot->pushMessage($user_id, BotHelper::getMenu());

//                    return $result->getHTTPStatus()." ".$result->getRawBody();
                }catch (Exception $e){
                    $result = $bot->replyText($event['replyToken'], $e->getMessage());

                    return $result->getHTTPStatus()." ".$result->getRawBody();
                }
            }
        }elseif($event['type'] == 'message'){
            $text = $event['message']['text'];
            $user = User::findOne(['user_id' => $user_id]);
            if(strtolower($text) == "start"){
                if($user->status == User::STATUS_LOOKING){
                    $bot->pushMessage($user_id, new TextMessageBuilder("Bot: We still looking for a person to talk to you :)"));
                }elseif ($user->status == User::STATUS_IN_CHAT){
                    $bot->pushMessage($user_id, new TextMessageBuilder("Bot: You are already in a talking session with other person :)"));
                }else{
                    $user->status = User::STATUS_LOOKING;
                    if($user->getChatMate()){
                        $user->save();
                        BotHelper::greetMate($user_id);
                    }else{
                        $user->save();
                        $bot->pushMessage($user_id, new TextMessageBuilder("Sorry, we couldn't find any person who are free to talk right now."));
                        $bot->pushMessage($user_id, new TextMessageBuilder("We will notify you when there is a persone that are ready to talk"));
                        $bot->pushMessage($user_id, new TextMessageBuilder("Please help share this bot. We need a lot of people for this to be fun :)"));
                    }
                }
            }elseif (strtolower($text) == "end_talk"){
                BotHelper::notifyEndMate($bot, $user->current_friend_id, true);
                $user->current_friend_id = '';
                $user->status = User::STATUS_IDLE;
                $user->save();
                $bot->pushMessage($user_id, new TextMessageBuilder("===========================\nThis talking session is ended."));
                $bot->pushMessage($user_id, new TextMessageBuilder("How was the talk? Is it nice?"));
                $bot->pushMessage($user_id, BotHelper::getMenu());
            }elseif (strtolower($text) == "chat_quota"){
                $bot->pushMessage($user_id, new TextMessageBuilder("Bot: You have {$user->chat_quota} left."));
            }elseif (strtolower($text) == 'menu'){
                $bot->pushMessage($user_id, BotHelper::getMenu());
            }else{
                if($user->chat_quota > 0 ){
                    if($event['message']['type'] == 'sticker'){
                        $bot->pushMessage($user->current_friend_id, new StickerMessageBuilder($event['message']['packageId'], $event['message']['stickerId']));
                    }else{
                        $bot->pushMessage($user->current_friend_id, new TextMessageBuilder($text));
                    }
                    $user->chat_quota = $user->chat_quota - 1;
                    if($user->chat_quota < 1){
                        BotHelper::notifyEndMate($bot, $user->current_friend_id, false);
                        $user->current_friend_id = '';
                        $user->status = User::STATUS_IDLE;
                        $bot->pushMessage($user_id, new TextMessageBuilder("===========================\nThis talking session is ended."));
                        $bot->pushMessage($user_id, new TextMessageBuilder("How was the talk? Is it nice?"));
                        $bot->pushMessage($user_id, BotHelper::getMenu());
                    }
                    $user->save();
                }else{
                    $bot->pushMessage($user_id, new TextMessageBuilder("Sorry, we dont't understand what are you saying :("));
                    $bot->pushMessage($user_id, BotHelper::getMenu());
                }
            }
        }else{
            $result = $bot->replyText($event['replyToken'], print_r($event, 1));

            return $result->getHTTPStatus()." ".$result->getRawBody();
        }
    }
});

$app->run();
