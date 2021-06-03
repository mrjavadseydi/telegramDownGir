<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Deposit;
use App\Models\Down;
use App\Models\Wallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class DataController extends KeyController
{
    public function addChannel($req)
    {

        $data = [];
        preg_match_all("[@\w*|https:\/\/\w*.*|https:\/\/\w*\.\w*\/\w*\/\w*|https:\/\/\w*\.\w*\/\w*]",$this->text,$match);
        if(!isset($match[0][0])){
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"در متن ارسالی شما هیچ تگ و کانالی یافت نشد !",
                'reply_markup'=> $this->back()
            ]);
            $this->setOption('');
            return "";
        }
        $ex = $match[0];
        foreach($ex as $e){
            $channels = Channel::where('channel',$e)->count();
            $i =0;

            if ($channels==0){
                $ch = Channel::create([
                    'chat_id'=>$this->chat_id,
                    'channel'=>$e,
                    'approve'=>false
                ]);

                $data[]= $ch['id'];
                $i++;
                $this->sendMessage([
                    'chat_id'=> $this->chat_id,
                    'text'=>"کانال ".$e."ثبت شد",
                ]);
            }else{
                $this->sendMessage([
                    'chat_id'=> $this->chat_id,
                    'text'=>"کانال ".$e."تکراری است",
                ]);
            }

        }
        $i = count($data);
        if($i>0){
            $uniq = uniqid();
            Cache::put($uniq, $data);
            $user_id = $req['message']['from']['username'] ?? $req['message']['from']['first_name'];
            $text = $req['message']['from']['id'] . "**^$uniq**^channel**^ \n کاربر : @$user_id \nایا لینک و یا تگ زیر مورد تایید است ؟ \n $this->text";
            $inline_markup = Keyboard::make([
                'inline_keyboard' => [
                    [
                        [
                            'text' => "تایید",
                            'callback_data' => "ok"."^^".$uniq
                        ],
                        [
                            'text' => 'رد',
                            'callback_data' => "deny"."^^".$uniq

                        ],
                        [
                            'text' => 'بلاک شخص',
                            'callback_data' => "blockuser"."^^".$this->chat_id

                        ]
                    ]
                ],
            ]);
            $this->sendMessage([
                'chat_id'=>$this->getdata('channel'),
                'text'=>$text,
                'reply_markup'=>$inline_markup
            ]);
        }
        $this->sendMessage([
            'chat_id'=> $this->chat_id,
            'text'=>" بعد از تایید تگ توسط ادمین میتوانید کانال خود را ثبت دان کنید در صورت داشتن تگ دیگر میتوانید تگ بعدی ارسال کنید",
            'reply_markup'=> $this->back()
        ]);

    }
    public function recive()
    {
        $w = Wallet::where('chat_id',$this->chat_id)->first()->amount;
        if($w>0){
        $this->sendMessage([
            'chat_id'=> $this->chat_id,
            'text'=>"موجودی شما : ".  $w."دلار
کارمزد هر برداشت %0.5 دلار میباشد ، لطفا مبلغ مورد نظر خود را برای برداشت ارسال کنید
اعداد حتمی لاتین باشد!
            "
            ,
            'reply_markup'=> $this->back()
        ]);
        $this->setOption('bardasht');
    }else{
        $this->sendMessage([
            'chat_id'=> $this->chat_id,
            'text'=>"موجودی شما کافی نمیباشد ",
            'reply_markup'=> $this->WalletKey()
        ]);
    }
    }
    public function callbackHandel($req)
    {
        $ex = explode('^^', $req['callback_query']['data']);
        // if()
        if($ex[0]=="ok"){
            try {
                Telegram::editMessageText([
                    'chat_id' =>$req['callback_query']['message']['chat']['id'],
                    'message_id' => $req['callback_query']['message']['message_id'],
                    'text' => $req['callback_query']['message']['text'] . "\n تایید شده "
                ]);
            } catch (TelegramResponseException $e) {
                echo 1;
            }
            $data = Cache::get($ex[1]);
            foreach($data as $d){
                Channel::whereId($d)->update([
                    'approve'=>true
                ]);
                if(Channel::whereId($d)->first()){
                    $cc = Channel::whereId($d)->first();
                }
            }
            $e = explode("**^ ",$req['callback_query']['message']['text']);

            $this->sendMessage([
                'chat_id'=>$e[0],
                'text'=>"کانال های شما تایید شدند"
            ]);


        }elseif($ex[0]=="deny"){
            try {
                Telegram::editMessageText([
                    'chat_id' =>$req['callback_query']['message']['chat']['id'],
                    'message_id' => $req['callback_query']['message']['message_id'],
                    'text' => $req['callback_query']['message']['text'] . "\n رد شده "
                ]);
            } catch (TelegramResponseException $e) {
                echo 1;
            }
            $data = Cache::get($ex[1]);
            foreach($data as $d){
                if( $cc = Channel::whereId($d)->first()){
                    $cc = Channel::whereId($d)->first();
                }
                Channel::whereId($d)->delete();

            }
            $e = explode("**^ ",$req['callback_query']['message']['text']);

            $this->sendMessage([
                'chat_id'=>$e[0],
                'text'=>"کانال های شما رد شدند "
            ]);

        }elseif($ex[0]=="blockuser"){
            try {
                Telegram::editMessageText([
                    'chat_id' =>$req['callback_query']['message']['chat']['id'],
                    'message_id' => $req['callback_query']['message']['message_id'],
                    'text' => $req['callback_query']['message']['text'] . "\n بلاک شده "
                ]);
            } catch (TelegramResponseException $e) {
                echo 1;
            }
            $this->sendMessage([
                'chat_id'=>$ex[1],
                'text'=>"شما بلاک شدید "
            ]);
            Cache::put('block'.$ex[1],"now");

        }
    }
    public function down($req){
        if(Channel::where([['chat_id',$this->chat_id],['approve',true]])->count()==0){
            $this->sendMessage([
                'chat_id'=>$this->chat_id,
                'text'=>'شما کانالی ثبت نکرده اید',
                'reply_markup'=>$this->back()
            ]);
            return "";
        }
        if($this->getdata('down')=="on"){
            $list = "";
            foreach(Channel::where([['approve',1],['chat_id',$this->chat_id]])->get() as $g){
                $list .=$g->channel."\n";
            }
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"لطفا تگ خود را ارسال کنید
تگ ها زیر هم باشند
لیست تگ های تایید شده شما:
$list
                ",
                'reply_markup'=> $this->back()
            ]);
            $this->setOption('downchose');

        }else{
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"ثبت دان فعال نیست",
                'reply_markup'=> $this->back()
            ]);
        }

    }
    public function downchose($req)
    {
        $list ="";
        preg_match_all("[@\w*|https:\/\/\w*.*|https:\/\/\w*\.\w*\/\w*\/\w*|https:\/\/\w*\.\w*\/\w*]",$this->text,$match);
        if(!isset($match[0][0])){
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"در متن ارسالی شما هیچ تگ و کانالی یافت نشد !",
                'reply_markup'=> $this->back()
            ]);
            $this->setOption('');
            return "";
        }
        foreach($match[0] as $c){
            if(Channel::where([['channel',$c],['approve',true],['chat_id',$this->chat_id]])->first()){
                $list = $list.$c."\n";
            }else{
                $this->sendMessage([
                    'chat_id'=> $this->chat_id,
                    'text'=>"کانال $c تایید نشده و یا متعلق به شما نیست ",
                    'reply_markup'=> $this->back()
                ]);
                $this->setOption('');
                return "";
            }
        }

        $user_id = $req['message']['from']['username'] ?? $req['message']['from']['first_name'];
        $this->sendMessage([
            'chat_id'=>$this->getdata('downg'),
            'text'=>$this->chat_id."**^down**^ \n
کاربر :@$user_id
            ".$list
        ]);
            foreach($match[0] as $l){
                Down::create([
                    'chat_id'=>$this->chat_id,
                    'down'=>$l,
                    'status'=>0
                ]);
            }

        $this->sendMessage([
            'chat_id'=> $this->chat_id,
            'text'=>" لیست دان شما \n $list \n برای ادمین ارسال شد ",
            'reply_markup'=> $this->back()
        ]);
        $this->setOption('');
    }
    public function handel_group($req)
    {
        if(isset($req['message']['reply_to_message']['text'])){
            $ex =explode('**^', $req['message']['reply_to_message']['text']);
            if(count($ex)<2){
                return "";
            }
            if($ex[1]=="money"||$ex[1]=="down"){
                try {
                    Telegram::editMessageText([
                        'chat_id' =>$req['message']['chat']['id'],
                        'message_id' =>$req['message']['reply_to_message']['message_id'],
                        'text' => $req['message']['reply_to_message']['text'] . "\n پاسخ داده شد\n  ".$this->text
                    ]);
                } catch (TelegramResponseException $e) {
                    echo 1;
                }
                $this->sendMessage([
                    'chat_id'=>$ex[0],
                    'text'=>":پیام مدیریت به شما \n".$this->text,
                    'reply_markup'=> $this->back()
                ]);
                if($ex[1]=="money"){
                    Deposit::where([['chat_id',$ex[0]],['message_id',$req['message']['reply_to_message']['message_id']]])->update([
                        'payed'=>true
                    ]);
                }
            }elseif($ex[1]=="confirm"){
                if($this->text=="y"){

                }else{
                    try{
                        Telegram::deleteMessage([
                            'chat_id' => $req['message']['chat']['id'],
                            'message_id' => $req['message']['reply_to_message']['message_id']
                        ]);
                    } catch (Exception $e) {
                    }
                }
            }elseif(isset($ex[2])&&$ex[2]=="channel"){
                try {
                    Telegram::editMessageText([
                        'chat_id' =>$req['message']['from']['id'],
                        'message_id' =>$req['message']['reply_to_message']['message_id'],
                        'text' => $req['message']['reply_to_message']['text'] . "\n پاسخ داده شد  ".$this->text
                    ]);
                } catch (TelegramResponseException $e) {
                    echo 1;
                }
                $this->sendMessage([
                    'chat_id'=>$ex[0],
                    'text'=>":پیام مدیریت به شما \n".$this->text,
                    'reply_markup'=> $this->back()
                ]);

            }
            elseif($ex[1]=="resid"&&$this->text=="/revert"){
                $data = Cache::get($ex[0]);
                $fean = Cache::get($this->chat_id);
                $per =$data['per'];
                $g = Cache::get($this->chat_id."counter");
                Cache::put($this->chat_id."counter",[
                    'shot'=>$g['shot'],
                    'view'=>$g['view']-$data['view']
                    ]);
                $w = Wallet::where('chat_id',$data['chat_id'])->first();
                if(($w->amount-$per)<0){
                    exit();
                }
                Wallet::where('chat_id',$data['chat_id'])->update([
                    'amount'=> $w['amount']-$per
                ]);
                $this->sendMessage([
                    'chat_id'=>$this->chat_id,
                    'text'=>"done",
                ]);
                // $this->sendMessage([
                //     'chat_id'=>1389610583,
                //     'text'=>print_r($data,true),
                // ]);
                try {
                    Telegram::deleteMessage([
                        'chat_id' =>$this->chat_id,
                        'message_id' =>$req['message']['reply_to_message']['message_id'],
                    ]);
                    Telegram::deleteMessage([
                        'chat_id' =>$data['chat_id'],
                        'message_id' =>$data['message_id'],
                    ]);

                } catch (TelegramResponseException $e) {
                    echo 1;
                }
            }
        }elseif(isset($req['message']['reply_to_message']['photo'])&&isset($req['message']['reply_to_message']['caption'])){
            $ex =explode('**^', $req['message']['reply_to_message']['caption']);

            if($ex[1]=="shot"){
                $e = explode("\n",$this->text);
                if(count($e)==1&&Cache::has($req['message']['chat']['id'])){
                    $fean = Cache::get($this->chat_id);
                    try{
                    $per = round(($e[0]*$fean['fee'])/$fean['dollar'],2);
                }catch(Exception $e){
                    $this->sendMessage([
                        'chat_id'=>$this->chat_id,
                        'text'=>"ورودی صحیح نیست",

                    ]);
                    return "";
                    exit();
                }
                    $g = Cache::get($this->chat_id."counter");
                    $i = intval($g['shot'])+1;
                    Cache::put($this->chat_id."counter",[
                        'shot'=>$i,
                        'view'=>$g['view']+$e[0]
                        ]);
                    $data = [
                        'chat_id'=>$ex[0],
                        'per'=>$per,
                        'fee'=>$fean['fee'],
                        'message_id'=>$req['message']['reply_to_message']['message_id'],
                        'text'=> $req['message']['reply_to_message']['caption'],
                        'uniq'=>$ex[2],
                        'view'=>$e[0],
                        'arz'=>$fean['dollar']
                    ];
                    $w = Wallet::where('chat_id',$data['chat_id'])->first();
                    $al = $w['amount']+$data['per'];
                    $d = Cache::get($data['uniq']);
                    if(!isset($d['tag'])){
                        $d['tag'] = " ";
                    }
                    $msg = "
                    ✅ رسید حسابرسی 🧮

🆔 Tags  :   ".$d['tag']."

👁 View : ".$data['view']." K
💶 amount : ".$data['fee']."
💵 voucherFee : ".$data['arz']."
💰 Deposit amount : ".$data['per']." $
🆔 User ID: ".$data['chat_id']."
📆Campaign :  ".Carbon::now()." - ".$req['message']['chat']['title']."
تراکنش با موفقیت انجام شد
💸 balance : ".$w['amount']."->".($al)."

";
Wallet::where('chat_id',$data['chat_id'])->update([
    'amount'=> $w['amount']+$data['per']
]);
                    $f = $this->sendMessage([
                        'chat_id'=>$data['chat_id'],
                        'text'=>$msg,
                        'reply_markup'=> $this->back()
                    ]);
                    $ar = [
                        'chat_id'=>$data['chat_id'],
                        'message_id'=>$f['message_id'],
                        'arz'=>$fean['dollar'],
                        'fee'=>$fean['fee'],
                        'view'=>$data['view'],
                        'per'=>$data['per']
                    ];
                    $un = uniqid();
                    Cache::put($un, $ar);
                    $this->sendMessage([
                        'chat_id'=>$this->chat_id,
                        'text'=>$un.'**^resid**^'.$msg,

                    ]);

                    try{

                        if(!Cache::has('log')){
                            Cache::put("log",$msg ."\n --------------\n");
                        }else{
                            $c = Cache::get('log');
                            Cache::put('log',$c.$msg ."\n --------------\n");
                        }
                    } catch (TelegramResponseException $e) {
                        echo 1;
                    }
                }
            }
        }
    }


}
