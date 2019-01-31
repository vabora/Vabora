<?php
namespace Core\Library;
class Request{
    /**
     * Request::all 获取所有HTTP METHOD
     * @param $key 需要获取的键值
     * @param $default 获取失败的默认显示值
     * @return string||array
     */
    static public function all($key='',$default=null){
        $method = array('POST','PUT','PATCH');
        if(in_array(self::server('REQUEST_METHOD'),$method)){
            return self::post($key,$default);
        }
        return self::get($key,$default);
    }

    /**
     * Request::get 实现$_GET
     * @param $key 需要获取的键值
     * @param $default 获取失败的默认显示值
     * @return string||array
     */
    static public function get($key='',$default=null){
        if($key==''){return $_GET;}
        return isset($_GET[$key])?$_GET[$key]:$default;
    }

    /**
     * Request::post 实现$_POST
     * @param $key 需要获取的键值
     * @param $default 获取失败的默认显示值
     * @return string||array 
     */
    static public function post($key='',$default=null){
        $data = $_POST;
        if(!strpos(strtolower(self::server('CONTENT_TYPE')),'multipart/form-data')){
            $tmp = json_decode(file_get_contents('php://input'),true);
            $data = array_merge($data,is_array($tmp)?$tmp:array());
        }
        if($key==''){return $data;}
        return isset($data[$key])?$data[$key]:$default;
    }
    /**
     * Request::method 获取REQUEST_METHOD
     * @return string
     */
    static public function method(){
        return $_SERVER['REQUEST_METHOD'];
    }
    /**
     * Request::server 实现$_SERVER
     * @param $key 需要获取的键值
     * @param $default 获取失败的默认显示值
     * @return string||array 
     */
    static public function server($key='',$default=null){
        if($key==''){return $_SERVER;}
        return isset($_SERVER[$key])?$_SERVER[$key]:$default;
    }
    /**
     * Request::session 实现$_SESSION
     * @param $key 需要获取的键值
     * @param $default 获取失败的默认显示值
     * @return string||array 
     */
    static public function session($key='',$default=null){
        if($key==''){return $_SESSION;}
        return isset($_SESSION[$key])?$_SESSION[$key]:$default;
    }
    /**
     * Request::cookie 实现$_COOKIE
     * @param $key 需要获取的键值
     * @param $default 获取失败的默认显示值
     * @return string||array 
     */
    static public function cookie($key='',$default=null){
        if($key==''){return $_COOKIE;}        
        return isset($_COOKIE[$key])?$_COOKIE[$key]:$default;
    }
    /**
     * Request::file 获取指定文件对象
     * @param $path 文件路径
     * @param $type [object||array] 指定返回的类型
     * @return object||array ||error return false
     */
    static public function file($path,$type='object'){
        if(!is_file($path)){return false;}
        $file = new \SplFileObject($path);
        if($type=='array'){
            $content = '';
            foreach($file as $line) {
                $content .= $line;
            }
            return array(
                'getATime' => $file->getATime(), //最后访问时间
                'getBasename' => $file->getBasename(), //获取无路径的basename
                'getCTime' => $file->getCTime(), //获取inode修改时间
                'getExtension' => $file->getExtension(), //文件扩展名
                'getFilename' => $file->getFilename(), //获取文件名
                'getGroup' => $file->getGroup(), //获取文件组
                'getInode' => $file->getInode(), //获取文件inode
                'getMTime' => $file->getMTime(), //获取最后修改时间
                'getOwner' => $file->getOwner(), //文件拥有者
                'getPath' => $file->getPath(), //不带文件名的文件路径
                'getPathInfo' => $file->getPathInfo(), //上级路径的SplFileInfo对象
                'getPathname' => $file->getPathname(), //全路径
                'getPerms' => $file->getPerms(), //文件权限
                'getRealPath' => $file->getRealPath(), //文件绝对路径
                'getSize' => $file->getSize(),//文件大小，，单位字节
                'getType' => $file->getType(),//文件类型 file dir link
                'isDir' => $file->isDir(), //是否是目录
                'isFile' => $file->isFile(), //是否是文件
                'isLink' => $file->isLink(), //是否是快捷链接
                'isExecutable' => $file->isExecutable(), //是否可执行
                'isReadable' => $file->isReadable(), //是否可读
                'isWritable' => $file->isWritable(), //是否可写
                'content'=>$content,//文件内容
                );
        }
        return $file;
    }
    /**
     * Request::folder 获取指定目录对象
     * @param $path 目录路径
     * @param $type [object||array] 指定返回的类型
     * @return object||array ||error return false
     */
    static public function folder($path,$type='object'){
        if(!is_dir($path)){return false;}
        $folder = new \DirectoryIterator($path);
        if($type=='array'){
            $child = array('folder'=>array(),'file'=>array());
            foreach ($folder as $item) {
                if ($item->isDir()&&!$item->isDot()) {
                    $child['folder'][] = array('name'=>$item->getRealPath(),'size'=>$item->getSize());
                }
                elseif($item->isFile()){
                    $child['file'][] = array('name'=>$item->getFileName(),'size'=>$item->getSize());    
                }
            }
            return array(
                'getATime' => $folder->getATime(), //最后访问时间
                'getBasename' => $folder->getBasename(), //获取无路径的basename
                'getCTime' => $folder->getCTime(), //获取inode修改时间
                'getFilename' => $folder->getFilename(), //获取文件名
                'getGroup' => $folder->getGroup(), //获取文件组
                'getInode' => $folder->getInode(), //获取目录inode
                'getMTime' => $folder->getMTime(), //获取最后修改时间
                'getOwner' => $folder->getOwner(), //目录拥有者
                'getPath' => $folder->getPath(), //目录路径
                'getPathInfo' => $folder->getPathInfo(), //上级路径的SplFileInfo对象
                'getPathname' => $folder->getPathname(), //全路径
                'getPerms' => $folder->getPerms(), //目录权限
                'getRealPath' => $folder->getRealPath(), //目录绝对路径
                'getSize' => $folder->getSize(),//目录大小，，单位字节
                'getType' => $folder->getType(),//目录类型 file dir link
                'isDir' => $folder->isDir(), //是否是目录
                'isDot' => $folder->isDot(), //是否是‘.’或‘..’
                'isFile' => $folder->isFile(), //是否是文件
                'isLink' => $folder->isLink(), //是否是快捷链接
                'isExecutable' => $folder->isExecutable(), //是否可执行
                'isReadable' => $folder->isReadable(), //是否可读
                'isWritable' => $folder->isWritable(), //是否可写
                'child'=>$child,//包含的子目录或文件集合
                );
        }
        return $folder;
    }
    /**
     * Request::http 获取访问链接参数集合
     * @return array 
     */
    static public function http(){
        return array(
            'domain'=>self::server('HTTP_HOST'),
            'port'=>self::server('SERVER_PORT'),
            'protocol'=>self::server('SERVER_PROTOCOL'),
            'language'=>self::server('HTTP_ACCEPT_LANGUAGE'),
            'url'=>'http://'.self::server('HTTP_HOST').self::server('REQUEST_URI'),
        );
    }
    /**
     * Request::browser 获取客户端浏览器
     * @return string 
     */
    static public function browser(){
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $browser = 'unkown';
        $list = array(
            'Mozilla Firefox'=>'/Firefox\/([^;)]+)+/i',
            'Microsoft Edge'=>'/Edge\/([\d\.]+)/i',
            'Internet Explorer'=>'/(MSIE\s+([^;)]+)+|rv:([\d\.]+))/i',
            'Opera'=>'/OPR\/([\d\.]+)/',
            'Google Chrome'=>'/Chrome\/([\d\.]+)/',
            'Apple Safari'=>'/\/([^;)]+)Safari/i',
        );
        foreach($list as $key=>$value){
            if(preg_match($value,$agent,$matches)){
                $browser = $key.' '.preg_replace('/(rv:|\/\w+)+/i','',$matches[1]);
                break;
            }
        }
        return $browser;
    }

    /**
     * Request::os 获取客户端操作系统
     * @return string 
     */
    static public function os(){
        $agent = self::server('HTTP_USER_AGENT');
        preg_match('/(\(.[^\)]+\))/',$agent,$matches);
        $tmp = count($matches)>0?$matches[0]:'unkown os';
        return preg_replace('/(\(|\))/','',$tmp);
    }

    /**
     * Request::clientIP 获取客户端真实IP
     * @return string 
     */
    static public function clientIP(){
        $default = '127.0.0.1';
        $ip = $default;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])&&strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'],$default)){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } 
        elseif ( isset($_SERVER['REMOTE_ADDR'])&&strcasecmp($_SERVER['REMOTE_ADDR'],$default)){
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $default;
        if($ip==$default){
            $str = file_get_contents('http://t.cn/RbRoteO');
            preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/",$str,$matches);
            $ip = count($matches)>0?$matches[0]:$default;
        }
        return filter_var($ip, FILTER_VALIDATE_IP);
    }
    /**
      * Request::serverIP 获取服务器真实IP
     * @return string 
     */
    static public function serverIP(){
        return GetHostByName($_SERVER['SERVER_NAME']);
    }
    /**
      * Request::area 获取客户端的地理位置
     * @return array 
     */
    static public function area(){
        $res = file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip='.self::clientIP());
        return json_decode($res,true);
    }
    /**
      * Request::spider 获取搜索引擎蜘蛛
     * @return string 
     */
    static public function spider(){
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $spider = "nospider";
        $spiders = array(
            'googlebot'=>'Google',
            'baiduspider'=>'Baidu',
            'bingbot'=>'Bing',
            'slurp'=>'Yahoo!',
            'yahoo'=>'Yahoo!',
            '360spider'=>'360',
            'sosospider'=>'Soso',
            'youdaobot'=>'Youdao',
            'yodaobot'=>'Youdao',
            'sogou'=>'Sogou',
            'sogouspider2'=>'Sogou',
            'msnbot'=>'MSN',
            'yisouspider'=>'Yisou',
            'ia_archiver'=>'Alexa',
            'easouspider'=>'Easou',
            'jikespider'=>'Jike',
            'etaospider'=>'Etao',
            'yandexbot'=>'Yandex',
            'ahrefsbot'=>'Ahrefs',
            'ezooms.bot'=>'Ezooms',
        );
        foreach($spiders as $key=>$value){
            if(strpos($agent,$key)!==false){$spider=$value;break;}
        }
        return $spider;
    }

}