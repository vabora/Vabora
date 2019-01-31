<?php
namespace Core;
use Core\Library\{Route,Config};
class App
{
    //缓存类地图
    static public $Maps = array();
    //应用运行函数
    static public function Run(){
        //初始化配置信息
        Config::init(ROOT_PATH.'Data/Config');
        if(!defined('APP_NAME')||!defined('APP_CONTROLLER')||!defined('APP_ACTION')){
            //启动路由判断
            Config::set('System.pathinfo',false);
            Config::set('System.Rewrite',false);
            if(Config::get('System.pathinfo')){
                $rewriteRule = Config::get('System.Rewrite')?'Data/Config/Rewrite.ini':null;
                Route::pathinfo($rewriteRule);//启动PATHINFO方式的路由[不推荐此方式]
            }
            else{
                Route::url('Core/Router.php');//启动强制ROUTE路由[建议使用此种方式]
            }
            //获取控制器
            $Controller = implode('\\',array('\\App',APP_NAME,'Controller',APP_CONTROLLER)).'Controller';
            //获取操作方法
            $Action = APP_ACTION;
            //创建应用实例
            $App = new $Controller();
            //调用应用实例方法
            if(method_exists($App,$Action)){
                $App->$Action();
            }
            else{
                die(':( 系统运行错误！操作方法：[Function '.$Action.'()]未定义。');
            }  
        }
        
    }
    /**
     * Loader 类加载器
     * @param string $class 类路径名称
     * @return bool or include class file
     */
    static public function Loader(string $class){
        if(!array_key_exists($class,self::$Maps)){
            $file = str_replace('\\','/',ROOT_PATH.$class.'.php');
            if(is_file($file)){
                require_once($file);//载入类文件
                self::$Maps[$class] = $file;
            }
            else{
                die(':( 系统运行错误！控制器类：[Class '.$class.'{}]未定义。');
            }
        }
        else{return true;}
    }
}
//调用类注册函数
spl_autoload_extensions('.php');
spl_autoload_register(__NAMESPACE__.'\App::Loader',true,true);