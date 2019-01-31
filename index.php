<?php
//运行调试开关[开发模式开启：On，上线模式关闭：Off]
ini_set('display_errors','On');
//PHP版本筛选
if (version_compare(PHP_VERSION, '7.0.0','<')) {
	header("Content-Type: text/html; charset=UTF-8");
    exit(':( PHP运行环境不能低于7.0.0');
}
use Core\App;
//定义根目录
define('ROOT_PATH',realpath('./').DIRECTORY_SEPARATOR);
//定义内核目录
define('CORE_PATH',ROOT_PATH.'Core');
//定义应用目录
define('APP_PATH',ROOT_PATH.'App');
//定义数据目录
define('DATA_PATH',ROOT_PATH.'Data');
//定义模板目录
define('TEMPLATE_PATH',ROOT_PATH.'Template');
//定义CSS目录
define('CSS_PATH','Public/Css');
//定义Image目录
define('IMG_PATH','Public/Image');
//定义JS目录
define('JS_PATH','Public/Js');
//加载应用启动文件
require_once(CORE_PATH.'/App.php');
//运行程序
App::Run();