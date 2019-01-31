<?php
namespace App\Home\Model;
use Core\Model;
class IndexModel extends Model
{
    public function getFields(){
        return $this->select($this->table,'*');
    }
}