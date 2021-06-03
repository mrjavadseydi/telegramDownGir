<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\Foreach_;
use Telegram\Bot\Keyboard\Keyboard;

class KeyController extends Controller
{
    public function back()
    {
        $btn = Keyboard::button([['برگشت 🔙']]);
        return Keyboard::make(['keyboard' => $btn, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }
    public function MainMenu()
    {
        $btn = Keyboard::button([['📸 ارسال شات 📸'], ['🧾 ثبت کانال 🧾','🆔 ثبت دان 🆔'], ['💰 کیف پول 💰']]);
        return Keyboard::make(['keyboard' => $btn, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }
    public function Shot()
    {
        $btn = Keyboard::button([['روزانه 🌞'], ['قبل شبانه 🌝'], ['پست آخر 🌚'],['برگشت 🔙']]);
        return Keyboard::make(['keyboard' => $btn, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }
    public function aprove()
    {
        $btn = Keyboard::button([['تایید میکنم '], ['انصراف']]);
        return Keyboard::make(['keyboard' => $btn, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }
    public function channelList()
    {
        $arr = [];
        foreach(Channel::where([['chat_id',$this->chat_id],['approve',true]])->get() as $b){
            $arr[] = [
                substr($b->channel,0,15)
            ];
        }
        $arr[] = ['انصراف'];
        $btn = Keyboard::button($arr);
        return Keyboard::make(['keyboard' => $btn, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }
    public function WalletKey()
    {
        $btn = Keyboard::button([['💵 موجودی حساب 💵'],['🎫 برداشت ووچر 🎫'], ['تراکنش ها'],['برگشت 🔙']]);
        return Keyboard::make(['keyboard' => $btn, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    }
}
