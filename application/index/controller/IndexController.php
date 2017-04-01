<?php

namespace app\index\controller;

use think\Response;
use think\View;

class IndexController
{
	public function index()
	{
		$view = new View();

		return $view->fetch('index');
	}

	public function test()
	{

		$content = date("m-d H:i:s")."--".$_SERVER["REMOTE_ADDR"].":".$_SERVER["REMOTE_PORT"]."--".$_SERVER["HTTP_USER_AGENT"]."\r\n";

		$content .= @var_export(json_decode(file_get_contents("php://input")), true);

		file_put_contents("D:/log.txt", $content."\r\n"."\r\n", FILE_APPEND);

		$r = new Response;
		$r->code(200)
		->content(0)
		->send();
		die();
	}


}
