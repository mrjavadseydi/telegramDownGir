<?php
namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Channel;
use App\Models\Down;
use App\Models\Option;
use App\Models\Wallet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpParser\Node\Stmt\Catch_;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Chat;

class TelegramController extends MenuController
{
    public $messageType;
    public $chat_id;
    public $text;
    public function index(Request $request)
    {
        try{


       // die();
        $req = $request->toArray();
        // Telegram::sendMessage([
        //     'chat_id'=>-581585114,
        //     'text'=>'up'
        // ]);
        //  $this->sendMessage(['chat_id' => 1389610583, 'text' => print_r($req, true) ]);
        if (isset($req['callback_query'])) {
            $this->callbackHandel($req);
            exit();
        }
        if(!Cache::has('downp')){
            Cache::put('downp','');
        }

        !isset($req['message']['text']) ? $req['message']['text'] = "//**" : '';
        $this->text = $req['message']['text'];
        $this->chat_id = isset($req['message']['chat']['id']) ? $req['message']['chat']['id'] : null;
        $this->messageType = $this->Type($req);
        if(Cache::has('block'.$this->chat_id)){
            $this->sendMessage([
                'chat_id'=>$this->chat_id,
                'text'=>"شما بلاک شدید "
            ]);
        }
        $this->handel_group($req);
        if(Admin::where('chat_id',$req['message']['from']['id'])->first()){
            if ($this->messageType == "message" && substr($req['message']['text'], 0, 6) == '/remch' && !empty(substr($req['message']['text'], 7))) {
                $token = substr($req['message']['text'], 7);
                Channel::where('channel',$token)->delete();
                $this->sendMessage([
                    'chat_id'=>$this->chat_id,
                    'text'=>"کانال حذف شد!"
                ]);
            }
            if ($this->messageType == "message" && substr($req['message']['text'], 0, 6) == '/rembl' && !empty(substr($req['message']['text'], 7))) {
                $token = substr($req['message']['text'], 7);
                Channel::where('channel',$token)->delete();
                Cache::pull("block".$token);
            }
            if ($this->messageType == "message" && substr($req['message']['text'], 0, 6) == '/setfe' && !empty(substr($req['message']['text'], 7))) {
                $token = substr($req['message']['text'], 7);
                $ex = explode(" " ,$token);
                if(count($ex) != 2){
                    exit();
                }
                    Cache::put($this->chat_id, [
                        'fee'=>$ex[0],
                        'dollar'=>$ex[1]
                    ]);
                    $this->sendMessage([
                        'chat_id'=>$this->chat_id,
                        'text'=>
                        "
                        فی هر کا : ".$ex[0]."
                        نرخ دلار : ".$ex[1]."
                        "
                    ]);

            }
            if($this->text=="/help"){
                $t = "
                روشن کردن دان
                /setop down on
                خاموش کردن دان و دریافت لیست
                /setop down off
                روشن کردن شات
                /setop shot on
                خاموش کردن شات
                /setop shot off
                چت ایدی گروه شات روزانه
                /setop roz {id}
                چت ایدی گروه پست اخر
                /setop pakhar {id}
                چت ایدی گروه قبل شبانه
                /setop gshab {id}
                چت ایدی گروه برداشت وجه
                /setop money {id}
                چت ایدی گروه تایید کانال
                /setop channel {id}
                چت ایدی گروه دان گیر
                /setop downg {id}
                تعیین فی و ارز
                /setop {fee} {arz}
                حذف ادمین
                /radmin {id}
                افزودن ادمین :
                /nadmin {id}
                لاگ
                /log
                حذف کانال
                /remch {tag}
                انبلاک فرد
                /rembl {id}
                روزانه
                /setop shot1 off
                قبل شبانه
                /setop shot2 off
                پست اخر
                /setop shot3 off
                ";
                $this->sendMessage([
                    'chat_id'=>$this->chat_id,
                    'text'=>$t
                ]);
            }
            if($this->text == "/log"){
                $log = Cache::pull('log');
                $this->sendMessage([
                    'chat_id'=>$this->chat_id,
                    'text'=>$log
                ]);
            }
            if ($this->messageType == "message" && substr($req['message']['text'], 0, 6) == '/setop' && !empty(substr($req['message']['text'], 7))) {
                $token = substr($req['message']['text'], 7);
                $ex = explode(" " ,$token);

                try{
                    $this->setdata($ex[0],$ex[1]);
                    $this->sendMessage([
                        'chat_id'=>$this->chat_id,
                        'text'=>'option updated'
                    ]);
                }catch(Exception $e){
                    $this->sendMessage([
                        'chat_id'=>$this->chat_id,
                        'text'=>'error'
                    ]);
                }
                if($ex[0]=="down"&&$ex[1]=="on"){
                    Down::where('status',1)->update([
                        'status'=>2
                    ]);
                        Down::where('status',0)->update([
                            'status'=>1
                        ]);
                }
                if($ex[0]=="down"&&$ex[1]=="off"){
                    $m = "";
                    foreach(Down::where('status',0)->get() as $i=> $d){
                        $m.=$d->down."\n";
                        if($i%50==0){
                            $this->sendMessage([
                                'chat_id'=>$this->chat_id,
                                'text'=>$m
                            ]);
                            $m='';
                        }

                    }
                    $this->sendMessage([
                        'chat_id'=>$this->chat_id,
                        'text'=>$m
                    ]);


                 }
            }
            if ($this->messageType == "message" && substr($req['message']['text'], 0, 7) == '/nadmin' && !empty(substr($req['message']['text'], 8))) {
                $token = substr($req['message']['text'], 8);
                Admin::create([
                    'chat_id'=> $token
                ]);
                $this->sendMessage([
                    'chat_id'=>$this->chat_id,
                    'text'=>"admin added"
                ]);
            }
            if ($this->messageType == "message" && substr($req['message']['text'], 0, 7) == '/radmin' && !empty(substr($req['message']['text'], 8))) {
                $token = substr($req['message']['text'], 8);
                Admin::where('chat_id',$token)->delete();
                $this->sendMessage([
                    'chat_id'=>$this->chat_id,
                    'text'=>"admin removed"
                ]);
            }
        }
        try{
            if(Wallet::where('chat_id',  $this->chat_id )->count()==0){
                Wallet::create([
                    'chat_id'=>  $this->chat_id,
                    'amount'=>0
                ]);
            }
        }catch(Exception $e){

        }

        if ($this->text == "/start" | $this->text == "برگشت 🔙" | $this->text == "انصراف" |$this->text == "بازگشت")
        {
            $this->start($req);
            exit();
        }
        switch ($this->getOption())
        {
            case "channelManage":
                $this->addChannel($req);
            break;
            case "SendShot":
                $this->choseChannel($req);
            break;
            case "sendPhoto":
                $this->sendPic($req);
            break;
            case "checkPic":
                $this->checkPic($req);
            break;
            case "acceptSend":
                $this->AcceptAndSend($req);
            break;
            case "bardasht":
                $this->minesMoney($req);
            break;
            case "downchose":
                $this->downchose($req);
            break;
        }
        switch ($this->text)
        {
            case "🧾 ثبت کانال 🧾":
                $this->ManageChannel($req);
            break;
            case "📸 ارسال شات 📸":
                $this->sendShot($req);
            break;
            case "💰 کیف پول 💰":
                $this->wallet($req);
            break;
            case "💵 موجودی حساب 💵":
                $this->amount($req);
            break;
            case "🎫 برداشت ووچر 🎫":
                $this->recive($req);
            break;
            case "🆔 ثبت دان 🆔":
                $this->down($req);
            break;
            case "/end":
                $this->end($req);
            break;
        }
    }catch(Exception $e){
        $message = $e->getMessage();
        $this->sendMessage([
            'chat_id'=>1389610583,
            'text'=>$message
        ]);
    }

    }
    public function Type($arr = [])
    {
        if (!isset($arr['message']['from']['id']) & !isset($arr['callback_query']))
        {
            die();
        }
        if (isset($arr['message']['photo']))
        {
            return 'photo';
        }
        elseif (isset($arr['message']['audio']))
        {
            return 'audio';
        }
        elseif (isset($arr['message']['document']))
        {
            return 'document';
        }
        elseif (isset($arr['message']['video']))
        {
            return 'video';
        }
        elseif (isset($arr['callback_query']))
        {
            return 'callback_query';
        }
        elseif (isset($arr['message']['contact']))
        {
            return 'contact';
        }
        elseif (isset($arr['message']['text']))
        {
            return 'message';
        }
        else
        {
            return null;
        }
    }
    public function sendMessage($arr)
    {
        try
        {
            return Telegram::sendMessage($arr);
        }
        catch(TelegramResponseException $e)
        {
            return "user has been blocked!";
        }
    }

    public function sendPhoto($arr)
    {
        try
        {
            return Telegram::sendPhoto($arr);
        }
        catch(TelegramResponseException $e)
        {
            return "user has been blocked!";
        }
    }

    public function getOption()
    {
        return Cache::get($this->chat_id);
    }
    public function setOption($data)
    {
        Cache::put($this->chat_id, $data);
    }
    public function setdata($key,$val)
    {
        if(Option::where('name',$key)->count()==0){
            Option::create([
                'name'=>$key,
                'value'=>$val
            ]);
        }else{
            Option::where('name',$key)->update([
                'value'=>$val
            ]);
        }
    }
    public function getdata($key){
        return Option::where('name',$key)->first()->value;
    }
    public function shotCounter($id){
        if(!Cache::has($id."counter")){
            Cache::put($id."counter",[
               'shot'=> 0,
               'view'=>0
               ]);
        }
    }





}

