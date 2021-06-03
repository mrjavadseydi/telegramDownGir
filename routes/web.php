<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('/',[\App\Http\Controllers\TelegramController::class,'index']);
Route::get('/c',function(){
dd(Cache::get('log'));
});
Route::get('table', function () {
    return view('table');
});
Route::get('init',function (){
  foreach(Channel::where('approve',1)->get() as $g){

    preg_match_all("[@\w*|https:\/\/\w*.*|https:\/\/\w*\.\w*\/\w*\/\w*|https:\/\/\w*\.\w*\/\w*]",$g->channel,$match);
    if(isset($match[0][0])){
        $g->update([
            'channel'=>$match[0][0]
        ]);
    }else{
        $g->delete();
    }

  }
});
