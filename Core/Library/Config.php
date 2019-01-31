<?php
namespace Core\Library;
/**
 * Config 配置类
 * @author vabora(王小平)
 * @create time 2018/7/31 14:23:22
 * @version 0.0.1
 */

class Config
{
    static protected $config = array(); //配置信息
    static private $configPath = null; //配置目录
    static private $configFile = []; //配置文件集合
    static private $currentFile = null; //当前操作的文件
    //构造函数
    private function __construct(string $configPath){
        if(!is_dir($configPath)){die('非法的配置文件目录');}
        $files = new \DirectoryIterator($configPath);
        foreach($files as $file){
            if($file->isFile()&&preg_match('/\w+\.ini$/',$file->getFileName())){self::$configFile[]=$file->getFileName();}
        }
        self::$configPath = rtrim($configPath,'/');
    }
    /**
     * Config::init 配置初始化函数
     * @param string $configPath
     */
    static public function init(string $configPath){
        new Config($configPath);
    }
    /**
     * Config::load 获取所有配置信息
     * @param $key 需要查找的键值
     * @return 失败时系统报错
     */
    static private function load(string $key=null){
        if(count(self::$configFile)==0){die('配置尚未初始化，或配置文件丢失！');}
        if($key==null){
            foreach(self::$configFile as $file){
                self::$config[rtrim($file,'.ini')] = require(self::$configPath.'/'.$file);
            }
            self::$currentFile = self::$configFile;
        }
        else{
            $file = $key;
            if(preg_match('/^(\w+\.)*\w+$/',$key)>0){
                $file = explode('.',$key)[0];
            }
            if(!self::find($file.'.ini')){die($file.'.ini 配置文件不存在！');}
            $filepath = self::$configPath.'/'.$file.'.ini';
            self::$config[$file] = require($filepath);
            self::$currentFile = $file.'.ini';
        }
    }
    /**
     * Config::find() 配置文件查找函数
     * @param string $name 文件名
     * @param bool $case 是否区分大小写[true:区分，false:不区分]
     * @return bool
     */
    static private function find(string $name,bool $case=false){
        $name = $case?$name:strtolower($name);
        foreach(self::$configFile as $file){
            if($name==($case?$file:strtolower($file))){
                return true;
            }
        }
        return false;
    }
    /**
     * Config::set 配置信息设置函数
     * @param string $key 需要设置的键值[如："name"或"user.name"]
     * @param mixed $value 需要设置的值
     * @return array||false 成功返回设置信息数组，失败返回false
     */
    static public function set(string $key,$value){
        self::load($key);//载入配置信息
        if(self::$config[rtrim(self::$currentFile,'.ini')]==null){
            self::$config[rtrim(self::$currentFile,'.ini')] = array();
        }
        if(preg_match('/^(\w+\.)*\w+$/',$key)>0){
            $data = explode('.',$key);
            if(count($data)==1){return array($data[0]=>$value);}
            while(count($data)-1){
                if(is_array($data)){
                    $count = count($data);
                    if($count>1){
                        $last = $count - 1;
                        $previous = $count - 2;
                        if(!is_array($data[$last])){$data[$last]=array($data[$last]=>$value);}
                        $data[$previous]= array($data[$previous]=>$data[$last]);
                        array_pop($data);
                    }
                    else{return false;}
                }
                else{return false;}
            }
            self::$config = array_replace_recursive(self::$config,$data[0]);//更新配置信息
            file_put_contents(self::$configPath.'/'.self::$currentFile,'<?php return ' . var_export(self::$config[rtrim(self::$currentFile,'.ini')],true).';');//写入配置文件
            return $data[0];
        }
        else{return false;}
    }
    /**
     * Config::get 配置信息获取函数
     * @param string $key 键值[如："name"或"user.name"]
     * @return mixed int||string||array 成功返回字符串或者数组
     */
    static public function get(string $key = null){
        self::load($key);//载入配置信息
        $config = self::$config;
        if(preg_match('/^(\w+\.)*\w+$/',$key)>0){
            $arr = explode('.',$key);
            while(count($arr)){
                $config = $config[$arr[0]];
                array_shift($arr);
            }
            return $config;
        }
        else{return $config;}
    }
    /**
     * Config::add 添加配置文件
     * @param string $name 配置文件名称
     * @param array $data 初始数据
     * @return bool 成功返回true，失败返回false；
     */
    static public function add(string $name,array $data=array()){
        if(preg_match('/[a-zA-Z]+/',$name)){
            if(self::find($name.'.ini')){die($name.'.ini 配置文件已经存在！');}
            $file = self::$configPath.'/'.$name.'.ini';
            if(fopen($file,'w+')){
                file_put_contents($file,'<?php return ' . var_export($data,true).';');//写入配置文件
                return true;
            }
            return false;
        }
        return false;
    }
    /**
     * Config::remove 移除配置文件
     * @param string $name 配置文件名称
     * @return bool 成功返回true，失败返回false；
     */
    static public function remove(string $name){
        if(preg_match('/[a-zA-Z]+/',$name)){
           $file = self::$configPath.'/'.$name.'.ini';
           if(is_file($file)){
            return unlink($file);
           }
           return false;
        }
        return false;
    }

}