<?php
// กรณีต้องการตรวจสอบการแจ้ง error ให้เปิด 3 บรรทัดล่างนี้ให้ทำงาน กรณีไม่ ให้ comment ปิดไป
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
// include composer autoload
require_once '/vendor/autoload.php';

// การตั้งเกี่ยวกับ bot
require_once 'bot_settings.php';


require_once '/services/CallFunction.php';
 
// กรณีมีการเชื่อมต่อกับฐานข้อมูล
//require_once("dbconnect.php");
 
///////////// ส่วนของการเรียกใช้งาน class ผ่าน namespace
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
//use LINE\LINEBot\Event;
//use LINE\LINEBot\Event\BaseEvent;
//use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use LINE\LINEBot\ImagemapActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder ;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\DatetimePickerTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
 
// เชื่อมต่อกับ LINE Messaging API
$httpClient = new CurlHTTPClient(LINE_MESSAGE_ACCESS_TOKEN);
$bot = new LINEBot($httpClient, array('channelSecret' => LINE_MESSAGE_CHANNEL_SECRET));
 
// คำสั่งรอรับการส่งค่ามาของ LINE Messaging API
$content = file_get_contents('php://input');


// กำหนดค่า signature สำหรับตรวจสอบข้อมูลที่ส่งมาว่าเป็นข้อมูลจาก LINE
$hash = hash_hmac('sha256', $content, LINE_MESSAGE_CHANNEL_SECRET, true);
$signature = base64_encode($hash);
 
// แปลงค่าข้อมูลที่ได้รับจาก LINE เป็น array ของ Event Object
$events = $bot->parseEventRequest($content, $signature);
$eventObj = $events[0]; // Event Object ของ array แรก
 
// ดึงค่าประเภทของ Event มาไว้ในตัวแปร มีทั้งหมด 7 event
$eventType = $eventObj->getType();
 
// สร้างตัวแปร ไว้เก็บ sourceId ของแต่ละประเภท
$userId = NULL;
$groupId = NULL;
$roomId = NULL;
// สร้างตัวแปร replyToken สำหรับกรณีใช้ตอบกลับข้อความ
$replyToken = NULL;
// สร้างตัวแปร ไว้เก็บค่าว่าเป้น Event ประเภทไหน
$eventMessage = NULL;
$eventPostback = NULL;
$eventJoin = NULL;
$eventLeave = NULL;
$eventFollow = NULL;
$eventUnfollow = NULL;
$eventBeacon = NULL;
// เงื่อนไขการกำหนดประเภท Event 
switch($eventType){
    case 'message': $eventMessage = true; break;    
    case 'postback': $eventPostback = true; break;  
    case 'join': $eventJoin = true; break;  
    case 'leave': $eventLeave = true; break;    
    case 'follow': $eventFollow = true; break;  
    case 'unfollow': $eventUnfollow = true; break;  
    case 'beacon': $eventBeacon = true; break;                          
}
// สร้างตัวแปรเก็บค่า groupId กรณีเป็น Event ที่เกิดขึ้นใน GROUP
if($eventObj->isGroupEvent()){
    $groupId = $eventObj->getGroupId();  
}
// สร้างตัวแปรเก็บค่า roomId กรณีเป็น Event ที่เกิดขึ้นใน ROOM
if($eventObj->isRoomEvent()){
    $roomId = $eventObj->getRoomId();            
}
// ดึงค่า replyToken มาไว้ใช้งาน ทุกๆ Event ที่ไม่ใช่ Leave และ Unfollow Event
if(is_null($eventLeave) && is_null($eventUnfollow)){
    $replyToken = $eventObj->getReplyToken();    
}
// ดึงค่า userId มาไว้ใช้งาน ทุกๆ Event ที่ไม่ใช่ Leave Event
if(is_null($eventLeave)){
    $userId = $eventObj->getUserId();
}
// ตรวจสอบถ้าเป็น Join Event ให้ bot ส่งข้อความใน GROUP ว่าเข้าร่วม GROUP แล้ว
if(!is_null($eventJoin)){
    $textReplyMessage = "ขอเข้ากลุ่มด้วยน่ะ GROUP ID:: ".$groupId;
    $replyData = new TextMessageBuilder($textReplyMessage);                 
}
// ตรวจสอบถ้าเป็น Leave Event เมื่อ bot ออกจากกลุ่ม
if(!is_null($eventLeave)){
     
}
// ตรวจสอบถ้าเป้น Message Event และกำหนดค่าตัวแปรต่างๆ
if(!is_null($eventMessage)){
    // สร้างตัวแปรเก็ยค่าประเภทของ Message จากทั้งหมด 8 ประเภท
    $typeMessage = $eventObj->getMessageType();  
    //  text | image | sticker | location | audio | video | imagemap | template 
    // ถ้าเป็นข้อความ
    if($typeMessage=='text'){
        $userMessage = $eventObj->getText(); // เก็บค่าข้อความที่ผู้ใช้พิมพ์
    }
    // ถ้าเป็น sticker
    if($typeMessage=='sticker'){
        $packageId = $eventObj->getPackageId();
        $stickerId = $eventObj->getStickerId();
    }
    // ถ้าเป็น location
    if($typeMessage=='location'){
        $locationTitle = $eventObj->getTitle();
        $locationAddress = $eventObj->getAddress();
        $locationLatitude = $eventObj->getLatitude();
        $locationLongitude = $eventObj->getLongitude();
    }       
    // เก็บค่า id ของข้อความ
    $idMessage = $eventObj->getMessageId();  
}
 
// แปลงข้อความรูปแบบ JSON ให้อยู่ในโครงสร้างตัวแปร array
$events = json_decode($content, true);
if(!is_null($events)){
    // ถ้ามีค่า สร้างตัวแปรเก็บ replyToken ไว้ใช้งาน
    $replyToken = $events['events'][0]['replyToken'];
    $typeMessage = $events['events'][0]['message']['type'];
    $userMessage = $events['events'][0]['message']['text'];

    switch ($typeMessage){
        case 'text':{
            switch ($userMessage) {
                case "คณะ":{
                    $replyData = text_faculty_show();
                }break;
                case "เกี่ยวกับคณะ":{
                    $replyData = text_abount_faculty_show();
                }break;
                case "AAA":{
                    $replyData = text_A();
                }break;
                case "B":{
                    $textReplyMessage = "คุณพิมพ์ B";
                    $replyData = new TextMessageBuilder($textReplyMessage);
                }break;
                default:break;
            }
        }break;
        default:break;
    }
}
 
//l ส่วนของคำสั่งตอบกลับข้อความ
$response = $bot->replyMessage($replyToken,$replyData);
if ($response->isSucceeded()) {
    echo 'Succeeded!';
    return;
}
 
// Failed
echo $response->getHTTPStatus() . ' ' . $response->getRawBody();
?>
