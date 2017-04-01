<?php
namespace app\index\controller;

use think\View;

class IndexController
{
    public function index()
    {
	    $view = new View();
	    return $view->fetch('index');
    }


}
