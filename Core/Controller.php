<?php
namespace Core;
class Controller
{
    protected $_View = null;
    protected $_Config = array();
    public function __construct(){
        $this->_Config = array(
            'file' => ROOT_PATH.'App/'.APP_NAME.'/View/'.APP_ACTION.'.html',
        );
        $this->_View = new View($this->_Config);
    }

    protected function model($table){
        $model = '\App\\'.APP_NAME.'\Model\\'.APP_ACTION.'Model';
        return new $model($table);
    }
    
    protected function assign($key,$value=''){
        $this->_View->assign($key,$value);
    }

    protected function display(){
        $this->_View->render();
    }

    protected function source(){
        return $this->_View->render(false);
    }
}