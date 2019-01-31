<?php
namespace Core;
use Core\Library\Config;
use Vendor\Medoo;
class Model extends Medoo
{
    public $table = '';
    public function __construct($table){
        $this->table = $table;
        $option = Config::get('database');
        parent::__construct($option);
    }
}