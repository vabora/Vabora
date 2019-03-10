<?php
/**
 * Route 路由类
 * @author vabora(王小平)
 * @create time 2018/8/5 14:23:22
 * @version 1.0.1
 */
namespace Core\Library;
class Route {
    //路由标
    static private $routes = array(); 
    //当前路由ID
    static private $current = -1;
    //子路由
    static private $route = array('rule'=>null,'url'=>null,'router'=>null,'type'=>array(),'data'=>array(),'where'=>array());
    //伪静态规则
    static private $rewriteRule = array();
    //路由构造函数
    private function __construct(string $rule,$router,array $type){
        if(in_array($rule,array_column(self::$routes,'rule'))){die($rule.' 路由已经存在！');}
        self::$route['rule'] = $rule;
        $tmp = self::_where($rule);
        self::$route['url'] = $tmp['url'];
        self::$route['router'] = $router;
        self::$route['type'] = $type;
        self::$route['where'] = $tmp['where'];
        self::$routes[] = self::$route;
        self::$current = count(self::$routes)-1;
    }
    /**
     * Route::pathinfo() 采用[PATHINFO]方式的访问
     * @param string $rewrite_file 伪静态规则文件
     */
    static public function pathinfo(string $rewrite_file=null){
        $uri = $_SERVER['REQUEST_URI'];
        if(!isset($uri)){die(':( 路由系统运行异常！缺少环境变量：[REQUEST_URI]');}
        if(isset($rewrite_file)&&!is_file($rewrite_file)){die('伪静态规则配置文件未找到！');}
        //载入路由规则文件
        if(is_file($rewrite_file)){
            self::$rewriteRule = require($rewrite_file);
            $rules = array_column(self::$rewriteRule,'rule');
            $urls = array_map(function($item){return $item=self::_where($item);},$rules);
            //var_dump($rules);
            //查询当前的uri在在伪静态规则表中的位置
            $index = self::rule_query($uri,array_column($urls,'url'));
            $where = $urls[$index]['where'];
            $parameter = self::rule_parameter($uri,$rules[$index],$where);
            foreach($parameter as $key=>$value){
                $_GET[$key] = $value;
            }
            //判断路由是否存在
            if($index==-1){die(':( URI解析错误，未找到对应路径');}
            $uri = self::$rewriteRule[$index]['path'];
        }
        $uri = explode('/',trim($uri,'/'));
        define('APP_NAME',isset($uri[0])?$uri[0]:'Home');
        define('APP_CONTROLLER',isset($uri[1])?$uri[1]:'Index');
        define('APP_ACTION',isset($uri[2])?$uri[2]:'Index');
        if(count($uri)>4){
            $param = array_slice($uri,3);
            if(count($param)%2>0){array_pop($param);}
            for($i=0;$i<count($param);$i+=2){
                if(!preg_match('/^[a-zA-Z]+[0-9]*$/',$param[$i])){continue;}
                //填充$_GET变量
                $_GET[$param[$i]] = $param[$i+1];
            }
        }

    }
    /**
     * Route::url() 采用[URL]方式的访问
     * @param string $route_file 路由规则文件
     */
    static public function url(string $route_file){
        $uri = $_SERVER['REQUEST_URI'];
        if(!isset($uri)){die(':( 路由系统运行异常！缺少环境变量：[REQUEST_URI]');}
        if(!is_file($route_file)){die('路由规则配置文件未找到！');}
        //载入路由规则文件
        require($route_file);
        //排查路由表是否有数据
        if(count(self::$routes)<1){die(':( 路由表无数据');}
        //查询当前的路由在在路由表中的位置
        $index = self::rule_query($uri,array_column(self::$routes,'url'));
        //判断路由是否存在
        if($index==-1){die(':( 非法的访问地址');}
        //审核请求方式
        if(!in_array(strtolower($_SERVER['REQUEST_METHOD']),self::$routes[$index]['type'])){die(':( 非法的访问方式');}
        //访问链接中的动态变量数据填充$_GET
        if(preg_match('/\{[a-zA-Z]+\}/',self::$routes[$index]['rule'])){
            foreach(self::rule_parameter($uri,self::$routes[$index]['rule'],self::$routes[$index]['where']) as $key=>$value){
                $_GET[$key]=$value;
            }  
        }
        $router = self::$routes[$index]['router'];
        //附加数据根据请求方式填充
        foreach(self::$routes[$index]['data'] as $key=>$value){
            if(count(array_intersect(self::$routes[$index]['type'],['post','put','patch']))>0){
                $_POST[$key]=$value;
            }
            else{$_GET[$key]=$value;}
        }
        //路由操作类型判断，并执行相应的操作
        if(is_string($router)){
            $route = explode('@',$router);
            define('APP_NAME',$route[1]);
            $route = explode('/',$route[0]);
            define('APP_CONTROLLER',$route[0]);
            define('APP_ACTION',$route[1]);
        }
        elseif($router instanceof \Core\View){
            $router->render();
            exit();
        }
        elseif(method_exists($router,'redirect')){
            $router->redirect();
            exit();
        }
        elseif($router instanceof \Closure){
            $parameter = self::rule_parameter($uri,self::$routes[$index]['rule'],self::$routes[$index]['where']);
            $fn = new \ReflectionFunction($router);
            $params = array();
            foreach($fn->getParameters() as $item){
                $value = isset($parameter[$item->getName()])?$parameter[$item->getName()]:null;
                $params[$item->getName()] = $value;
            }
            call_user_func_array($router,$params);
            exit();
        }
        else{die(':( 非法的路由操作！');}
    }
    /**
     * Route::rule_query() 路由规则rule查询
     * @param string $uri 用户浏览器获取的uri
     * @param array $rules 路由规则rules集合
     * @return int 成功返回数组下标，失败返回-1
     */
    static private function rule_query(string $uri,array $rules){
        foreach($rules as $index=>$rule){
            //echo $rule;
			if(preg_match('/^\/\^.*/',$rule)){
				if(preg_match($rule,$uri)){return $index;}
			}
			else{
				if($uri==$rule){return $index;}
			}
		}
		return -1;
    }
    /**
     * Route::rule_parameter 路由参数提取函数
     * @param string $uri 用户浏览器获取的uri
     * @param string $rule 原始路由规则数据
     * @param array $where 路由参数条件
     */
    static private function rule_parameter(string $uri,string $rule,array $where){
        if(!preg_match('/\{[a-zA-Z]+\}/',$rule)){return array();}
        preg_match('/\{.+\}/',$rule,$parameterName);
        $key = array_map(function($item){return '/\{'.$item.'\}/';},array_keys($where));
        $value = array_map(function($item){return '('.$item.')';},array_values($where));
        $parameterReg = preg_replace($key,$value,$parameterName[0]);
        preg_match('/'.$parameterReg.'/',$uri,$parameter);
        return array_combine(array_keys($where), array_slice($parameter,1));
    }
    /**
     * Route::rule() 路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     * @param array $type 请求方法[get,post,put,delete,patch,options]
     */
    static public function rule(string $rule,$router,array $type){
        if(preg_match('/^\/\w*/',$rule)==0){die(':( 路由规则URL设置格式有误，必须以[/]开头');}
        if(count(array_intersect($type,['get','post','put','delete','patch','options']))!=count($type)){
            die(':( 非法的路由请求方式');
        }
        if(is_string($router)&&preg_match('/^\w+\/\w+\@\w+$/',$router)<0){
            die(':( 路由格式有误，请使用[Controller/Action@App]的格式！');
        }
        if(preg_match('/\{[a-zA-Z]+\}/',$rule)){
            preg_match_all('/\{[a-zA-Z]+\}/',$rule,$match);
            if(count(array_unique($match[0]))!=count($match[0])){
                die(':( 路由参数名称不能重复');
            }
        }
        return new Route($rule,$router,$type);
    }
    /**
     * Route::_where() 默认条件处理
     * @param string $rule 路由规则字符串
     * return array 返回设置后的url,where
     */
    static private function _where(string $rule){
        $where = array();
        $url = $rule;
        if(preg_match('/\{[a-zA-Z]+\}/',$rule)){
            preg_match_all('/\{[a-zA-Z]+\}/',$rule,$tags);
            foreach($tags[0] as $tag){
                $tag = preg_replace('(\{|\})','',$tag);
                if($tag=='id'){$where[$tag]='[0-9]+';}
                else{$where[$tag]='[a-zA-Z]+';}
            }
            foreach($where as $key=>$value){
			    $url=preg_replace('/\{'.$key.'\}/',$value,$url);
            }
            $url = '/^\\'.$url.'$/';
        }
        return array('url'=>$url,'where'=>$where);
    }

    /**
     * list() 获取路由列表数据
     * @param string $key 要获取的路由的指定数列，[当为空数组时返回完整路由表]
     * @return array route list
     */
    static public function list(string $key=null){
        return array_column(self::$routes,$key);
    }

    /**
     * auth() 路由命名函数
     * @param string $name 路由名称[格式：controller:action]
     * @param string $description 路由描述[格式：分组:操作]
     */
    public function auth(string $name,string $description){
        if(!preg_match('/^[a-zA-Z]+:[a-zA-Z]+$/',$name)){die('路由名称格式有误，[格式：controller:action]');}
        if(!preg_match('/^[\x{4e00}-\x{9fa5}]+:[\x{4e00}-\x{9fa5}]+$/u',$description)){die('路由描述格式有误，[格式：分组:操作]');}
        if(in_array($name,array_column(array_column(self::$routes,'auth'),'name'))){die("[{$name}]-路由名称重复！");}
        if(in_array($description,array_column(array_column(self::$routes,'auth'),'description'))){die("[{$description}]-路由描述重复！");}
        self::$routes[self::$current]['auth']['name'] = $name;
        self::$routes[self::$current]['auth']['description'] = $description;
		return $this;
    }

    /**
     * where() 路由变量的数据类型附加条件
     * @param array $where 参数附加条件
     * return Route;
     */
    public function where(array $where){
        $url = self::$routes[self::$current]['rule'];
        if(preg_match('/\{[a-zA-Z]+\}/',$url)){
            preg_match_all('/\{[a-zA-Z]+\}/',$url,$tags);
            $tmp = array_map(function($tag){
                return preg_replace('(\{|\})','',$tag);
            },$tags[0]);
            foreach($where as $key=>$value){
                if(!in_array($key,$tmp)){die(':( 路由参数命名错误，请对应rule{*}参数名称');}
            }
            $where = array_merge(self::$routes[self::$current]['where'],$where);
            self::$routes[self::$current]['where'] = $where;
		    foreach($where as $key=>$value){
			    $url=preg_replace('/\{'.$key.'\}/',$value,$url);
		    }
            self::$routes[self::$current]['url'] = '/^\\'.$url.'$/';
        }
        return $this;
    }

    /**
     * data() 路由附加数据参数
     * @param array $data 路由数据
     */
    public function data(array $data){
        self::$routes[self::$current]['data'] = $data;
		return $this;
    }

    /**
     * clear() 路由参数清空
     * @param array $keys 数据
     */
    public function clear(array $keys=[]){
        $data = self::$routes[self::$current]['data'];
        if(count($keys)==0){$keys = array_keys($data);}
        foreach($keys as $key){
            unset($data[$key]);
        }
        self::$routes[self::$current]['data'] = $data;
		return $this;
    }

    /**
     * Route::get() $_GET请求路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     */
    static public function get(string $rule,$router){
        return self::rule($rule,$router,['get']);
    }

    /**
     * Route::post() $_POST请求路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     */
    static public function post(string $rule,$router){
        return self::rule($rule,$router,['post']);
    }

    /**
     * Route::put() $_PUT请求路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     */
    static public function put(string $rule,$router){
        return self::rule($rule,$router,['put']);
    }

    /**
     * Route::delete() $_DELETE请求路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     */
    static public function delete(string $rule,$router){
        return self::rule($rule,$router,['delete']);
    }

    /**
     * Route::patch() $_PATCH请求路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     */
    static public function patch(string $rule,$router){
        return self::rule($rule,$router,['patch']);
    }

    /**
     * Route::options() $_POTIONS请求路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     */
    static public function options(string $rule,$router){
        return self::rule($rule,$router,['options']);
    }

    /**
     * Route::any() 任何请求路由规则定义
     * @param string $rule 路由规则
     * @param mixed $router 路由路径/闭包函数
     */
    static public function any(string $rule,$router){
        return self::rule($rule,$router,['get','post','put','delete','patch','options']);
    }

    /**
     * Route::redirect() 路由跳转
     * @param string $rule 路由规则
     * @param string $url 目标路径
     * @param int $state 状态码
     */
    static public function redirect(string $rule,string $url,int $state=301){
        $router = new class{
            public $url = null;
            public $state = null;
            public function redirect(){
                header('Location: '.$this->url,true,$this->state);
            }
        };
        $router->url = $url;
        $router->state = $state;
        self::rule($rule,$router,['get']);
    }

    /**
     * Route::view() 路由视图
     * @param string $rule 路由规则
     * @param string $path 路由路径/闭包函数
     * @param array $data 视图数据
     */
    static public function view(string $rule,string $path,array $data=[]){
        $router = new \Core\View(['file'=>$path]);
        foreach($data as $key=>$value){
            $router->assign($key,$value);
        }
        self::rule($rule,$router,['get']);
    }
}

