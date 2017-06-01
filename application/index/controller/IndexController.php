<?php

namespace app\index\controller;

use think\Response;
use think\View;

class IndexController
{
	public function Index()
	{
		$view = new View();

		return $view->fetch('index');
	}

	public function Hooks()
	{

		$content = date("m-d H:i:s");//."--".$_SERVER["REMOTE_ADDR"].":".$_SERVER["REMOTE_PORT"]."--".$_SERVER["HTTP_USER_AGENT"]."\r\n";

		$content .= @var_export(json_decode(file_get_contents("php://input"), true), true);

		file_put_contents("D:/log_hooks.txt", $content."\r\n"."\r\n", FILE_APPEND);

		$r = new Response;
		$r->code(200)
			->content(0)
			->send();
		die();
	}

	public function HeartBeat()
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

	public function index1()
	{

		// 创建一个Worker监听2345端口，使用http协议通讯
		$http_worker = new \Workerman\Worker("http://0.0.0.0:2345");

		// 启动4个进程对外提供服务
		$http_worker->count = 4;

		// 接收到浏览器发送的数据时回复hello world给浏览器
		$http_worker->onMessage = function ($connection, $data)
		{
			// 向浏览器发送hello world
			$connection->send('hello world');
		};

		// 运行worker
		\Workerman\Worker::runAll();
	}

	public function SendMail()
	{
		$mail = new \Email('smtp.exmail.qq.com', 465, "Waln370828", 'wangchongquan@hacfin.com', "王崇全1");
		$mail->sendMail("测试邮件", '这是一封测试邮件', [
			'qfsz168@163.com',
			'591572471@qq.com',
		], '/mnt/volume1/files/2017/6/1/11/30e7b93b-a444-8a78-518d-af9bd71c37d2_thumb/200', "tupian.png");
		die();
	}


}
