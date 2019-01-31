<?php
use Core\Library\{Route,Request,Config};
//路由配置信息
Route::get('/{name}-{id}-{page}.html',function($name,$id,$page){
     echo("{$id}----{$name}--{$page}");
 })->where(['page'=>'[0-9]+'])->auth('user:add','用户:添加');
Route::get('/a','index/index@home')->data(['name'=>'vabora'])->auth('user:update','用户:更新');
// Route::post('/upload','index/upload@home');
Route::redirect('/{name}.jpg','/404.html');
Route::get('/',function($id){echo 'hello world';});
Route::view('/hello.html','test.php');

Route::get('/{name}-{id}.html','index/test@home')->data(['name1'=>'yifeng'])->clear();

Route::get('/list',function(){
    print_r(Route::list());
});
