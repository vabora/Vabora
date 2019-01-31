<?php
namespace Core\Library;
class Response{
    /**
     * Response::Write 输出数据
     * @param $data
     * 
     */
    static public function Write($data){
        if(!is_string($data)){var_dump($data);}
        else{echo $data;}
    }
}