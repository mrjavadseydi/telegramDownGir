<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Down;
use App\Models\Wallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends DataController
{
    public function start($req){
       $f=  $this->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => "به ربات خوش آمدید  ",
            'reply_markup' => $this->MainMenu()
        ]);

        $this->setOption(' ');
    }
    public  function ManageChannel($req)
    {
        $this->sendMessage([
            'chat_id'=>$this->chat_id,
            'text'=>'کانال جدید را برای افزودن ارسال کنید',
            'reply_markup'=>$this->back()
        ]);

        $this->setOption('channelManage');
    }
    public function sendShot()
    {
        if($this->getdata('shot')=="off"){
            $this->sendMessage([
                'chat_id'=>$this->chat_id,
                'text'=>'وقت ارسال شات به اتمام رسید',
                'reply_markup'=>$this->back()
            ]);
            return "";
        }
        if(Channel::where([['chat_id',$this->chat_id],['approve',true]])->count()==0){
            $this->sendMessage([
                'chat_id'=>$this->chat_id,
                'text'=>'شما کانالی ثبت نکرده اید',
                'reply_markup'=>$this->back()
            ]);
            return "";
        }

        if(!Down::where([['status',1],['chat_id',$this->chat_id]])->first()&!Down::where([['status',0],['chat_id',$this->chat_id]])->first()){
            $this->sendMessage([
                'chat_id'=>$this->chat_id,
                'text'=>'⚠️⚠️⚠️اخطار : شما دان ثبت نکرده اید !⚠️⚠️⚠️
                ',
                'reply_markup'=>$this->back()
            ]);

            return '';
        }
        $this->sendMessage([
            'chat_id'=>$this->chat_id,
            'text'=>'انتخاب کنید',
            'reply_markup'=>$this->Shot()
        ]);

        $this->setOption('SendShot');
    }
    public function choseChannel(){
        if($this->text == "روزانه 🌞"){
            if($this->getdata('shot1')=="off"){
                $this->sendMessage([
                    'chat_id'=> $this->chat_id,
                    'text'=>"شات روزانه فعال نمیباشد !",
                    'reply_markup'=> $this->back()
                ]);
                $this->setOption(' ');
                return " ";
            }

        }elseif($this->text == "قبل شبانه 🌝"){
            if($this->getdata('shot2')=="off"){
                $this->sendMessage([
                    'chat_id'=> $this->chat_id,
                    'text'=>"شات قبل شبانه فعال نمیباشد !",
                    'reply_markup'=> $this->back()
                ]);
                $this->setOption(' ');
                return " ";
            }
        }else{
            if($this->getdata('shot3')=="off"){
                $this->sendMessage([
                    'chat_id'=> $this->chat_id,
                    'text'=>"شات پست اخر فعال نمیباشد !",
                    'reply_markup'=> $this->back()
                ]);
                $this->setOption(' ');
                return " ";
            }
        }
        Cache::put('shot'.$this->chat_id, $this->text);
        $list = "";
        foreach(Channel::where([['approve',1],['chat_id',$this->chat_id]])->get() as $g){
            $list .=$g->channel."\n";
        }
        $this->sendMessage([
            'chat_id'=> $this->chat_id,
            'text'=>"لطفا تگ خود را ارسال کنید\n
لیست تگ های تایید شده شما :
$list",
            'reply_markup'=> $this->back()
        ]);
        $this->setOption('sendPhoto');
    }
    public function sendPic($req){
        $list = "";
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
            Cache::put('channel+'.$this->chat_id, $list);
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"لیست کانال های تایید شده \n $list \n عکس را ارسال کنید",
                'reply_markup'=> $this->back()
            ]);
            $this->setOption('checkPic');

    }
    public function checkPic($req)
    {
        if($this->messageType=="photo"){


            // if(!Cache::has('photos')){
            //     Cache::put("photos",[$req['message']['photo'][0]['file_id']]);
            // }else{
            //     $c = Cache::get('log');
            //     if(in_array($req['message']['photo'][0]['file_id'],$c)){
            //         $this->sendMessage([
            //             'chat_id'=> $this->chat_id,
            //             'text'=>"عکس تکراری است!",
            //             'reply_markup'=> $this->back()
            //         ]);

            //     }
            //     $c[] = $req['message']['photo'][0]['file_id'];
            //     Cache::put('photos',$c);
            //     exit();
            // }
            Cache::put('pic'.$this->chat_id, $req['message']['photo'][0]['file_id']);

            $user_id = $req['message']['from']['username'] ?? $req['message']['from']['first_name'];
            $channel = str_replace('cahnnel+','',Cache::get('channel+'.$this->chat_id));
            $type = str_replace('shot','',Cache::get('shot'.$this->chat_id));

            $this->sendPhoto([
                'photo'=>$req['message']['photo'][0]['file_id'],
                'chat_id'=>$this->chat_id,
                'caption'=> "
کاربر: @$user_id
کانال: $channel
نوع : $type

آیا تایید میکنید  این پیام برای ادمین ارسال شود؟؟
                ", 'reply_markup'=> $this->aprove()
            ]);
            $this->setOption('acceptSend');
        }else{
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"عکس را ارسال کنید",
                'reply_markup'=> $this->back()
            ]);
        }
    }
    public function AcceptAndSend($req){
        if($this->text=="تایید میکنم"){
            $user_id = $req['message']['from']['username'] ?? $req['message']['from']['first_name'];
            $channel = str_replace('cahnnel+','',Cache::pull('channel+'.$this->chat_id));
            $type = str_replace('shot','',Cache::pull('shot'.$this->chat_id));
            $uniq = uniqid();
            $info = [
                'date'=>Carbon::now(),
                'tag'=>$channel,
                'type'=>$type
            ];
            Cache::put($uniq,$info);

            if($type == "روزانه 🌞"){
                $end = $this->getdata('roz');
            }elseif($type == "قبل شبانه 🌝"){
                $end =  $this->getdata('gshab');
            }else{
                $end = $this->getdata('pakhar');
            }
            $this->shotCounter($end);


            $this->sendPhoto([
                'photo'=>Cache::pull('pic'.$this->chat_id),
                'chat_id'=>$end,
                'caption'=> $this->chat_id."**^shot**^".$uniq."**^
کاربر: @$user_id
کانال: $channel
نوع : $type

                "
            ]);
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"ارسال شد ، جهت بازگشت دکمه بازگشت را انتخاب کنید",
                'reply_markup'=> $this->back()
            ]);
            $this->setOption('');

        }
    }
    public function wallet(){
        $this->sendMessage([
            'chat_id'=> $this->chat_id,
            'text'=>"انتخاب کنید ",
            'reply_markup'=> $this->WalletKey()
        ]);
        $this->setOption('walletList');

    }
    public function amount(){
        $this->sendMessage([
            'chat_id'=> $this->chat_id,
            'text'=>"موجودی شما : ". Wallet::where('chat_id',$this->chat_id)->first()->amount ."دلار",
            'reply_markup'=> $this->WalletKey()
        ]);
        $this->setOption('walletList');
    }
    public function minesMoney($req)
    {
        if(filter_var($this->text, FILTER_VALIDATE_FLOAT)==false){
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"ورودی غلط",
                'reply_markup'=> $this->back()
            ]);
            return " ";
            exit();
        }
        if($this->text < 1){
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"مقدار درخواستی شما کمتر از حد مجاز میباشد",
                'reply_markup'=> $this->back()
            ]);
            return " ";
            exit();
        }
        try{
        $mines =round($this->text,2);
        $c1 = (0.5*$mines)/100;
        $mines = $mines-$c1;
        }catch(Exception $e){
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"ورودی غلط",
                'reply_markup'=> $this->back()
            ]);
            return " ";
            exit();
        }
        $w = Wallet::where('chat_id',$this->chat_id)->first()->amount;
        $rwal = $w-(round($this->text,2)+$c1);
        if($this->text<=$w){
            $user_id = $req['message']['from']['username'] ?? $req['message']['from']['first_name'];
            $remain = $w - $c1;
            $this->sendMessage([
                'chat_id'=>$this->getdata('money'),
                'text'=>$this->chat_id."**^money**^
کاربر:$user_id
موجودی هم اکنون : $w $
موجودی بعد از برداشت  کارمزد :$mines $
مبلغ برداشت:$this->text $
"
            ]);
            Wallet::where('chat_id',$this->chat_id)->update([
                'amount'=>round($w-(round($this->text,2)),2)
            ]);
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"برای مدیریت ارسال شد ، جهت بازگشت دکمه بازگشت را انتخاب کنید",
                'reply_markup'=> $this->back()
            ]);
            $this->setOption('');

        }else{
            $this->sendMessage([
                'chat_id'=> $this->chat_id,
                'text'=>"موجودی شما : ".  $w."دلار
کارمزد هر برداشت 0.5 دلار میباشد ، لطفا مبلغ مورد نظر خود را برای برداشت ارسال کنید
 اعداد حتمی لاتین باشد!
 مقدار ارسالی قبلی صحیح نمیباشد
                "
                ,
                'reply_markup'=> $this->back()
            ]);
        }
    }
    public function end($req){
        if(isset($req['message']['chat']['title'])&&Cache::has($this->chat_id."counter")){
            $data = Cache::get($this->chat_id."counter");
            $fean = Cache::get($this->chat_id);
            $shot = $data['shot'];
            $view = $data['view'];
            $ri = $view *$fean['fee'];
            $view = $view+15;
            $do = round( $ri / $fean['dollar'],2);
            $id = $this->chat_id;
            $date = Carbon::now();
            $name = $req['message']['chat']['title'];
            $msg = "
جزییات حساب

تعداد کل شات ها :  $shot

تعداد ویو ها : $view k

مبلغ کل به تومان : ".number_format($ri)."

مبلغ کل به دلار : $do $

چت ایدی گروه : $id

گروه : $name

تاریخ :  $date


            ";
            $this->sendMessage([
                'chat_id'=>$id,
                'text'=>$msg
            ]);
        }
    }
}
