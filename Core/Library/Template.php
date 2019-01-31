<?php
namespace Core\Library;
/**
 * Template 模板引擎类
 * @author vabora(王小平)
 * @create time 2018/8/9 20:23:22
 * @version 1.0.1
 */
class Template{
    //模板引擎配置项
    private $config = array(
        //定义模板源文件路径及文件扩展名
        'template'=>array('path'=>null,'extension'=>'.html'),
        //定义编译后保存路径机文件扩展名
        'source'=>array('path'=>null,'extension'=>'.php'),
        //是否开启缓存[true保存编译文件，false直接编译时时更新]
        'cache'=>false,
    );
    //定义编译后的内容代码
    private $code = null;
    //定义模板变量集合
    private $variables = [];
    //定义模板标签集合
    private $tags = [
        //变量标签
        'variable' => [
            '/{(\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)}/i' => "<?php echo $1; ?>",
			'/\$(\w+)\.(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']['\\4']",
			'/\$(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']",
			'/\$(\w+)\.(\w+)/is' => "\$\\1['\\2']",
        ],
        //常量标签
        'constance' => [
			'/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s' => "<?php echo \\1;?>",
        ],
        //文件载入标签
        'include' => [
			'/{include\s*file=\"(.*)\"}/i' => "<?php \$this->render(\"$1\"); ?>",
        ],
        //条件语句标签
        'if' => [
            '/\{if\s+(.+?)\}/' => "<?php if(\\1) { ?>",
            '/\{else\}/' => "<?php } else { ?>",
            '/\{elseif\s+(.+?)\}/' => "<?php } elseif (\\1) { ?>",
            '/\{\/if\}/' => "<?php } ?>",
        ],
        //枚举标签
        'switch' => [
            '/\{switch\s+(\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\}/' => "<?php switch($1){ ?>",
            '/\{case\s+(\"?.+?\"?)\}/' => "<?php case $1: ?>",
            '/\{\/case\}/' => "<?php ;break; ?>",
            '/\{\/switch\}/' => "<?php } ?>",
        ],
        //循环标签
        'for' => [
            '/\{for\s+(.+?)\}/' => "<?php for(\\1) { ?>",
            '/\{\/for\}/' => "<?php } ?>",
        ],
        //数组遍历标签
        'foreach' => [
            '/\{foreach\s+(\S+)\s+as\s+(\S+)\}/' => "<?php \$n=1;if(is_array(\\1)) foreach(\\1 as \\2) { ?>", 
            '/\{foreach\s+(\S+)\s+as\s+(\S+)\s*=>\s*(\S+)\}/' => "<?php \$n=1; if(is_array(\\1)) foreach(\\1 as \\2 => \\3) { ?>",
            '/\{\/foreach\}/' => "<?php \$n++;}unset(\$n); ?>",
        ],
        //内置函数标签
        'function' => [
            '/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/' => "<?php echo \\1;?>",
			'/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/' => "<?php echo \\1;?>",
        ],
        //多余字符去除标签
        'fixed' => [
            '/\}(\n|\r|\\s{2,}|\t)+/' =>'}',
        ],
    ];

    /**
     * assign 变量赋值函数
     * @param string $name 变量名
     * @param mixed $value 变量值
     */
    public function assign(string $name, $value=null) {
		if(preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/',$name)){
			$this->variables[$name] = $value;
		}
    }
    
    /**
     * render 模板渲染函数
     * @param string $name 模板名称
     * @param bool $output 是否输出显示
     * @return string content 编译后的内容
     */
    private function render(string $name){
        extract($this->variables, EXTR_OVERWRITE);
        if($this->config['cache']){
            if($this->search_cache($name)){
                include($this->search_cache($name));
            }
            else{
                $this->code = $this->compile($name);
                include($this->config['source']['file']);
            }
        }
        else{
            $this->code = $this->compile($name);
            try {
                ob_start();
                @eval('?>'.$this->code);
                $this->code = ob_get_contents();
                ob_end_clean();
                echo $this->code;
            } catch (ParseError $e) {
                exit('模版解析错误: '.$e->getMessage()."\n");
            }
        }
    }

    /**
     * compile 源码编译函数
     * @param string $name 模板名称
     * @return string 返回编译后的代码
     */
    private function compile(sring $name){
        $template = $this->config['template'];
        $template['file'] = rtrim($template['path'],'/')."/{$name}".$template['extension'];
        if(!is_file($template['file'])){die('模板文件：'.$template['file'].'不存在！');}
        $source = file_get_contents($template['file']);
        $labels = array_values($this->tags);
        $code = preg_replace(array_keys(labels),array_values(labels),$source);
        if($this->config['cache']){
            $this->save_cache($template['file'],$code);
        }
        return $code;
    }

    /**
     * search_cache 搜索编译文件
     * @param string $name 模板名称
     * @return mixed [false：不存在，path：缓存文件路径]
     */
    private function search_cache(string $name){
        $template = $this->config['template'];
        $name = rtrim($template['path'],'/')."/{$name}".$template['extension'];
        $source = $this->config['source'];
        $name = md5($name).$source['extension'];
        $files = new \DirectoryIterator($source['path']);
        foreach($files as $file){
            if($name==$file->getFileName()){
                return rtrim($source['path'],'/').'/'.$name;
            }
        }
        return false;
    }

    /**
     * save_cache 缓存编译文件
     * @param string $name 模板名称
     * @param string $code 编译后的代码
     * @return mixed [false：缓存失败，path：成功缓存后的路径]
     */
    private function save_cache(string $name,string $code){
        $source = $this->config['source'];
        $source['file'] = rtrim($source['path'],'/').'/'.md5($name).$source['extension'];
        if(file_put_contents($source['file'],$code)){
            return $source['file'];
        }
        else{return false;}
    }
    
}