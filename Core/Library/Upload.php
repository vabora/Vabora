<?php
namespace Core\Library;
class Upload
{
    private $files = array();//存储上传信息的数组
    private $data = array();//上传数据
    private $mimes = array();//mime-type信息列表
    public $allowType = array();//允许上传的类型，默认不限制
    public $maxSize = 2*1024*1024;//最大允许文件大小，默认为系统设定2MB
    public $limit = -1;//运行同时上传的文件数量
    public $savePath = null;//文件保存路径
    public $saveName = null;//文件保存名称

    public function __construct(string $field=null){
        $this->files = isset($field)?$_FILES[$field]:$_FILES;
        $this->mimes = $this->get_mimes();
    }
    /**
     * upload 上传文件
     * @param string $path 设置上传路径
     * @return array 上传结果信息
     */
    public function upload(string $path=null){
        $upload_list = $this->upload_filter();
        $this->data['success'] = array();
        if(count($upload_list)>0){
            $this->savePath = isset($path)?$path:$this->savePath;
            if(!isset($this->savePath)){die('请设置上传路径');}
            $isdir = true;
            if(!is_dir($this->savePath)){$isdir = mkdir($this->savePath,0777,true);}
            if($isdir){
                $tmp = array();
                foreach($upload_list as $list){
                    $name = (isset($this->saveName)?$this->saveName:md5(uniqid(microtime(true),true))).'.'.$list['extension'];
                    $url = rtrim(ROOT_PATH.$this->savePath,'/').'/'.$name;
                    $isupload = move_uploaded_file($list['source'],$url);
                    $tmp[]=array(
                        'field'=>$list['field'],
                        'source'=>$list['name'],
                        'name'=>$name,
                        'url'=>$url,
                        'type'=>$list['type'],
                        'size'=>$list['size'],
                        'image'=>$list['image']
                    );
                }
                $this->data['success'] = $tmp;
                return $this->data;
            }
        }
        else{return $this->data;}
    }
    /**
     * upload_filter 对上传列表进行条件过滤
     * @return array 返回筛选后的上传列表
     */
    protected function upload_filter(){
        $list = $this->files_filter();
        $data = array();
        if(array_key_exists('success',$list)){
            foreach($list['success'] as $file){
                $result = $this->isvalid($file);
                if($result['status']){
                    $data[] = array(
                        'field'=>$file['field'],
                        'type'=>$file['type'],
                        'size'=>$file['size'],
                        'name'=>$file['name'],
                        'source'=>$file['tmp_name'],
                        'image'=>getimagesize($file['tmp_name'])==false?false:true,
                        'extension'=>$this->get_type($file['name'])
                    );
                }
                else{
                    $file['error']=9;
                    $file['message']=$result['message'];
                    $list['fail'][] = $file;
                }
            }
        }
        $this->data['error'] = array_key_exists('fail',$list)?$list['fail']:array();
        return $data;
    }
    /**
     * files_filter 对$_FILES上传信息进行初级过滤
     * @return array 返回过滤后的上传列表
     */
    protected function files_filter(){
        $files = $this->files;
        $mimes = $this->mimes;
        $msg = array(
            '文件上传成功',
            '上传的文件超过了 php.ini 中[ upload_max_filesize ]选项限制的值',
            '上传文件的大小超过了 HTML 表单中[ MAX_FILE_SIZE ]选项指定的值',
            '文件只有部分被上传',
            '没有文件被上传',
            '可疑文件[内容与文件类型不符]',
            '找不到临时文件夹',
            '文件写入失败'
        );
        $tmp = array();
        foreach($files as $field=>$file){
            if(is_string($file['name'])){
                $mime = $this->get_mime_type($file['tmp_name'],$file['type']);
                $type = $this->get_type($file['name'],true);
                $code = $mime!=$type?5:$file['error'];
                $tmp[$file['error']==0&&$mime==$type?'success':'fail'][] = array(
                    'name'=>$file['name'],
                    'type'=>$mime,
                    'tmp_name'=>$file['tmp_name'],
                    'error'=>$code,
                    'message'=>$msg[$code],
                    'size'=>$file['size'],
                    'field'=>$field
                );
            }
            else{
                for($i=0;$i<count($file['name']);$i++){
                    $mime = $this->get_mime_type($file['tmp_name'][$i],$file['type'][$i]);
                    $type = $this->get_type($file['name'][$i],true);
                    $code = $mime!=$type?5:$file['error'][$i];
                    $tmp[$file['error'][$i]==0&&$mime==$type?'success':'fail'][] = array(
                        'name'=>$file['name'][$i],
                        'type'=>$this->get_mime_type($file['tmp_name'][$i],$file['type'][$i]),
                        'tmp_name'=>$file['tmp_name'][$i],
                        'error'=>$code,
                        'message'=>$msg[$code],
                        'size'=>$file['size'][$i],
                        'field'=>$field
                    );
                }
            }
        }
        return $tmp;
    }
    /**
     * get_mime_type 获取真实的mime类型
     * @param string $file 文件路径
     * @return string mime
     */
    protected function get_mime_type(string $file){
        $mime = false;
        if(is_file($file)){
            if(function_exists('mime_content_type')){
                $mime = mime_content_type($file);
            }
            if(function_exists('finfo_file')&&function_exists('finfo_open')&&defined('FILEINFO_MIME_TYPE')){
                $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE),$file);
            }
        }
        return $mime;
    }
    /**
     * get_type 获取扩展名
     * @param string $file 文件路径
     * @param bool $ismime 是否返回mime字符串
     * @return string 默认返回文件扩展名
     */
    protected function get_type(string $file,bool $ismime=false){
        $ext = pathinfo($file,PATHINFO_EXTENSION);
        if(isset($ext)&&array_key_exists($ext,$this->mimes)){
            return trim($ismime?$this->mimes[$ext]:$ext);
        }
        else{return false;}
    }
    /**
     * isvalid 判断文件类型是否合法
     * @param string $file 文件名
     * @return array 状态和信息
     */
    protected function isvalid(array $file){
        if(!is_array($this->allowType)){die('设置allowType属性是出错，数据类型应为Array');}
        if(!is_int($this->maxSize)){die('设置maxSize属性是出错，数据类型应为Integer');}
        $result = array('status'=>true,'message'=>'文件验证通过');
        if($file['size']<=$this->maxSize){
            if(count($this->allowType)>0){
                if(!in_array($this->get_type($file['name']),$this->allowType)){
                    $result['status']=false;
                    $result['message']='非法的文件类型';
                }
            }
        }
        else{$result = array('status'=>false,'message'=>'文件超过最大2MB');}
        return $result;
    }
    /**
     * get_mimes 获取预定义的mime-type列表
     * return array mime信息表
     */
    protected function get_mimes(){
        return array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'flif' => 'image/flif',
            'svg' => 'image/svg+xml',
            'flv' => 'video/x-flv',
            'js' => 'application/x-javascript',
            'json' => 'application/json',
            'tiff' => 'image/tiff',
            'css' => 'text/css',
            'xml' => 'application/xml',
            'doc' => 'application/msword',
            'xls' => 'application/vnd.ms-excel',
            'xlt' => 'application/vnd.ms-excel',
            'xlm' => 'application/vnd.ms-excel',
            'xld' => 'application/vnd.ms-excel',
            'xla' => 'application/vnd.ms-excel',
            'xlc' => 'application/vnd.ms-excel',
            'xlw' => 'application/vnd.ms-excel',
            'xll' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pps' => 'application/vnd.ms-powerpoint',
            'rtf' => 'application/rtf',
            'pdf' => 'application/pdf',
            'html' => 'text/html',
            'htm' => 'text/html',
            'php' => 'text/x-php',
            'txt' => 'text/plain',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'mp3' => 'audio/mpeg3',
            'wav' => 'audio/wav',
            'aiff' => 'audio/aiff',
            'aif' => 'audio/aiff',
            'avi' => 'video/msvideo',
            'wmv' => 'video/x-ms-wmv',
            'mov' => 'video/quicktime',
            'zip' => 'application/zip',
            'tar' => 'application/x-tar',
            'swf' => 'application/x-shockwave-flash',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ott' => 'application/vnd.oasis.opendocument.text-template',
            'oth' => 'application/vnd.oasis.opendocument.text-web',
            'odm' => 'application/vnd.oasis.opendocument.text-master',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'otg' => 'application/vnd.oasis.opendocument.graphics-template',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'otp' => 'application/vnd.oasis.opendocument.presentation-template',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'odc' => 'application/vnd.oasis.opendocument.chart',
            'odf' => 'application/vnd.oasis.opendocument.formula',
            'odb' => 'application/vnd.oasis.opendocument.database',
            'odi' => 'application/vnd.oasis.opendocument.image',
            'oxt' => 'application/vnd.openofficeorg.extension',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
            'thmx' => 'application/vnd.ms-officetheme',
            'onetoc' => 'application/onenote',
            'onetoc2' => 'application/onenote',
            'onetmp' => 'application/onenote',
            'onepkg' => 'application/onenote',
            'csv' => 'text/csv',
        );
    }
}