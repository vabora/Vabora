<?php
namespace App\Home\Controller;
use Core\Controller;
use Core\Library\{Request,Upload,Config};
class IndexController extends Controller
{
    public function Index(){
        $this->assign('user',Request::get('name'));
        $this->display();
    }
    
    public function Upload(){
        echo json_encode(Request::post('firstName'));
    }

    public function test(){
        var_dump(Request::get());
    }
    
}