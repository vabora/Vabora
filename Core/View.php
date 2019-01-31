<?php
namespace Core;
class View
{
	protected $config = array();//配置信息
	protected $labels = array();//标签信息
	protected $variables = array();//变量集合
	protected $template = '';//模板信息
	public function __construct($config) {
		$this->config = $config;
		$this->assign('__View', $this);
		$this->labels = array(    
			/**
			 * clear qoute
			 */    
			'/\}(\n|\r|\\s{2,}|\t)+/' =>'}', 
			/**variable label
				{$name} => <?php echo $name;?>
				{$user['name']} => <?php echo $user['name'];?>
				{$user.name}    => <?php echo $user['name'];?>
			*/  
			'/{(\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)}/i' => "<?php echo $1; ?>",
			'/\$(\w+)\.(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']['\\4']",
			'/\$(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']",
			'/\$(\w+)\.(\w+)/is' => "\$\\1['\\2']",
			
			/**constance label
			{CONSTANCE} => <?php echo CONSTANCE;?>
			*/
			'/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s' => "<?php echo \\1;?>",
			
			/**include label
				{include file="test"}
			*/                              
			'/{include\s*file=\"(.*)\"}/i' => "<?php \$__View->render(\"$1\"); ?>",
			
			/**if label
				{if $name==1}       =>  <?php if ($name==1){ ?>
				{elseif $name==2}   =>  <?php } elseif ($name==2){ ?>
				{else}              =>  <?php } else { ?>
				{/if}               =>  <?php } ?>
			*/              
			'/\{if\s+(.+?)\}/' => "<?php if(\\1) { ?>",
			'/\{else\}/' => "<?php } else { ?>",
			'/\{elseif\s+(.+?)\}/' => "<?php } elseif (\\1) { ?>",
			'/\{\/if\}/' => "<?php } ?>",
			/**switch label
				{switch $arg}  => <?php swtich($arg){ ?>
				{case "arg1"}   =><?php case "arg1" ?>
				{/case} => <?php ;break; ?>
				{/switch}   =><?php }?>
			 */
			'/\{switch\s+(\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\}/' => "<?php switch($1){ ?>",
			'/\{case\s+(\"?.+?\"?)\}/' => "<?php case $1: ?>",
			'/\{\/case\}/' => "<?php ;break; ?>",
			'/\{\/switch\}/' => "<?php } ?>",
			/**for label
				{for $i=0;$i<10;$i++}   =>  <?php for($i=0;$i<10;$i++) { ?>
				{/for}                  =>  <?php } ?>
			*/              
			'/\{for\s+(.+?)\}/' => "<?php for(\\1) { ?>",
			'/\{\/for\}/' => "<?php } ?>",
			
			/**foreach label
				{foreach $arr as $vo}           =>  <?php $n=1; if (is_array($arr) foreach($arr as $vo){ ?>
				{foreach $arr as $key => $vo}   =>  <?php $n=1; if (is_array($array) foreach($arr as $key => $vo){ ?>
				{/foreach}                  =>  <?php $n++;}unset($n) ?> 
			*/
			'/\{foreach\s+(\S+)\s+as\s+(\S+)\}/' => "<?php \$n=1;if(is_array(\\1)) foreach(\\1 as \\2) { ?>", 
			'/\{foreach\s+(\S+)\s+as\s+(\S+)\s*=>\s*(\S+)\}/' => "<?php \$n=1; if(is_array(\\1)) foreach(\\1 as \\2 => \\3) { ?>",
			'/\{\/foreach\}/' => "<?php \$n++;}unset(\$n); ?>",
			
			/**function label
				{date('Y-m-d H:i:s')}   =>  <?php echo date('Y-m-d H:i:s');?> 
				{$date('Y-m-d H:i:s')}  =>  <?php echo $date('Y-m-d H:i:s');?> 
			*/
			'/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/' => "<?php echo \\1;?>",
			'/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/' => "<?php echo \\1;?>", 
        );
		
	}
	
	public function assign($name, $value = '') {
		if( is_array($name) ){
			array_merge($this->variables,$name);
		} else {
			$this->variables[$name] = $value;
		}
	}

	public function render($display=true){
		extract($this->variables, EXTR_OVERWRITE);
		try{
			if(!is_file($this->config['file'])){
				throw new \Exception('模版文件：'.$this->config['file'].'获取失败！', 500);
			}
			$this->template = file_get_contents($this->config['file']);
		}
		catch(\Exception $e){echo $e->getMessage();}
		$this->template = preg_replace(array_keys($this->labels),array_values($this->labels),$this->template);
		try {
			ob_start();
			@eval('?>'.$this->template);
			$this->template = ob_get_contents();
			ob_end_clean();
			if($display){echo $this->template;}
			else{return $this->template;}
		} catch (ParseError $e) {
			exit('模版解析错误: '.$e->getMessage()."\n");
		}
	}
}