<?php

namespace application\admin\controller;

use think\Response;

class HeartbeatController extends BaseController
{
	public function Index()
	{

		$content = date("m-d H:i:s")."--".$_SERVER["REMOTE_ADDR"].":".$_SERVER["REMOTE_PORT"]."--".$_SERVER["HTTP_USER_AGENT"]."\r\n";

		$content .= @var_export(json_decode(file_get_contents("php://input"), true), true);

		file_put_contents("D:/log_heart_beat.txt", $content."\r\n"."\r\n", FILE_APPEND);

		$r = new Response;
		$r->code(200)
			->content(0)
			->send();
		die();
	}
}
