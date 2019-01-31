<?php
/*
* 缓存类 cache
*/
define("cacheRoot","./cache/");
define("time",0);
define("name","");
define("cacheFileExt","php");
class cache {
//缓存目录
var $cacheRoot = "./cache/";
//缓存更新时间秒数，0为不缓存
var $cacheLimitTime = 0;
//缓存文件名
var $cacheFileName = "";
//缓存扩展名
var $cacheFileExt = "php";
/*
  * 构造函数,文件名、有效期
  * int $time 缓存更新时间
  */
function cache($name,$time ) {
  if( intval( $time ) )
  $this->cacheLimitTime = $time;
  //$this->cacheLimitTime = $cacheLimitTime;
  $this->cacheFileName = cacheRoot.$name.".".cacheFileExt;    //如：/cache/order.php
  ob_start();
} 
/*
  * 检查缓存文件是否在设置更新时间之内
  * 返回：如果在更新时间之内则返回文件内容，反之则返回失败
  */
function cacheCheck(){
  if( file_exists($this->cacheFileName) ) {            //1、是否存在文件,使用绝对路径 ./cache/xx
      $cTime = $this->getFileCreateTime($this->cacheFileName);
      if( $cTime + $this->cacheLimitTime > time() ) {   //2、文件是否有效
      echo file_get_contents( $this->cacheFileName );   //输出文件内容到缓存内存中
      $cacheContent = ob_get_contents();                //读取缓存内存中的数据
      ob_end_flush();
      
      return $cacheContent;     //输出文件缓存
      exit;
      }else{    //过期，删除再新建
         $r = @unlink( $this->cacheFileName );
         return false;
      }
  }
  return false;
}
/*
  * 读取缓存文件或者输出静态：逻辑==》1、第一次缓存文件不存在，先写缓存，立马读缓存；2、读了之后马上创建文件file
  * string $staticFileName 静态文件名（含相对路径）
  */
function caching( $staticFileName = "" ){
  if( $this->cacheFileName ) {
    $cacheContent = ob_get_contents();
    ob_end_flush();
    if( $staticFileName ) {
        $this->saveFile( $staticFileName, $cacheContent );
    }
    if( $this->cacheLimitTime )
    $this->saveFile( $this->cacheFileName, $cacheContent );
  }
} 
/*
  * 清除缓存文件
  * string $fileName 指定文件名(含函数)或者all（全部）
  * 返回：清除成功返回true，反之返回false
  */
function clearCache( $fileName = "all" ) {
  if( $fileName != "all" ) {
  $fileName = $this->cacheRoot . strtoupper(md5($fileName)).".".$this->cacheFileExt;
  if( file_exists( $fileName ) ) {
  return @unlink( $fileName );
  }else return false;
  }
  if ( is_dir( $this->cacheRoot ) ) {
  if ( $dir = @opendir( $this->cacheRoot ) ) {
  while ( $file = @readdir( $dir ) ) {
  $check = is_dir( $file );
  if ( !$check )
  @unlink( $this->cacheRoot . $file );
  }
  @closedir( $dir );
  return true;
  }else{
  return false;
  }
  }else{
  return false;
  }
}
/*根据当前动态文件生成缓存文件名*/
function getCacheFileName() {
  return $this->cacheRoot . strtoupper(md5($_SERVER["REQUEST_URI"])).".".$this->cacheFileExt;
  //return $this->cacheRoot . strtoupper(md5($_SERVER["REQUEST_URI"])).".".$this->cacheFileExt;
  /* $_SERVER["QUERY_STRING"]  获取查询 语句，实例中可知，获取的是?后面的值
    $_SERVER["REQUEST_URI"]   获取 http://localhost 后面的值，包括/aaa/index.php?p=222&q=333
    $_SERVER["SCRIPT_NAME"]   获取当前脚本的路径，如：index.php
    $_SERVER["PHP_SELF"]      当前正在执行脚本的文件名
   */
}
/*
  * 缓存文件建立时间
  * string $fileName 缓存文件名（含相对路径）
  * 返回：文件生成时间秒数，文件不存在返回0
  */
function getFileCreateTime( $fileName ) {
  if( ! trim($fileName) ) return 0;
  if( file_exists( $fileName ) ) {
  return intval(filemtime( $fileName ));
  }else return 0;
} 
/*
  * 保存文件
  * string $fileName 文件名（含相对路径）
  * string $text 文件内容
  * 返回：成功返回ture，失败返回false
  */
function saveFile($fileName, $text) {
  if( ! $fileName || ! $text ) return false;
  if( $this->makeDir( dirname( $fileName ) ) ) {
  if( $fp = fopen( $fileName, "w" ) ) {
  if( @fwrite( $fp, $text ) ) {
  fclose($fp);
  return true;
  }else {
  fclose($fp);
  return false;
  }
  }
  }
  return false;
}
/*
  * 连续建目录
  * string $dir 目录字符串
  * int $mode 权限数字
  * 返回：顺利创建或者全部已建返回true，其它方式返回false
  */
function makeDir( $dir, $mode = "0777" ) {
  if( ! $dir ) return 0;
  $dir = str_replace( "\\", "/", $dir );
  $mdir = "";
  foreach( explode( "/", $dir ) as $val ) {
  $mdir .= $val."/";
  if( $val == ".." || $val == "." || trim( $val ) == "" ) continue;
  if( ! file_exists( $mdir ) ) {
  if(!@mkdir( $mdir, $mode )){
  return false;
  }
  }
  }
  return true;
}
}

//使用DEMO
// include( "cache.php" );
// $cache = new cache(fileName,7100);  //实例化对象：在/cache/目录下，创建一个fileName的文件，有效期7100秒
// $cache->cacheCheck();    //检查文件1、是否存在 2、是否有效；真=返回文件内容，假=返回false
// echo date("Y-m-d H:i:s");//写入缓存
// $cache->caching();       //没有参数将上面echo的时间先读出来，在保存缓存文件；有参数，输出参数对应的缓存内容