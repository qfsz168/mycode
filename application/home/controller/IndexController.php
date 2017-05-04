<?php

namespace application\home\controller;

use GatewayClient\Gateway;
use think\Controller;
use think\View;

class IndexController extends Controller
{
	public function Index()
	{
		$view = new View();

		return $view->fetch('index');
	}

	public function Index1()
	{
		$view = new View();

		return $view->fetch('index1');
	}

	public function Index2()
	{
		$view = new View();

		return $view->fetch('index2');
	}

	public function Index3()
	{
		$view = new View();

		return $view->fetch('index3');
	}

	public function Bind($client_id)
	{

		// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
		Gateway::$registerAddress = '123.207.120.116:1238';

		// client_id与uid绑定
		Gateway::bindUid($client_id, 1);

		// 加入某个群组（可调用多次加入多个群组）
		Gateway::joinGroup($client_id, 2);
	}

	public function Send($content="")
	{
		Gateway::$registerAddress = '123.207.120.116:1238';

		// 向任意uid的网站页面发送数据
		Gateway::sendToUid(1, $_SERVER["REMOTE_ADDR"]."：".$content);
		// 向任意群组的网站页面发送数据
//		Gateway::sendToGroup(2, "123");
	}
}