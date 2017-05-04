<?php
/**
 * Created by PhpStorm.
 * User: WangChongquan
 * Date: 2017/1/25 0025
 * Time: 10:05
 */

namespace application\home\exception;

use think\Config;
use think\exception\Handle;
use think\exception\HttpException;
use think\Request;
use think\Response;

class Http extends Handle
{
	public function render(\Exception $e)
	{
		if ($e instanceof HttpException)
		{
			$errCode = $e->getStatusCode();
		}
		if (!isset($errCode))
		{
			$errCode = $e->getCode();
		}

		$msg = $e->getMessage();

		//处理参数错误
		if (strpos($msg, "method param miss") !== false)
		{
			$arr = explode(":", $msg);

			$errCode = \EC::PARAM_ERROR;
			$msg     = \EC::GetMsg($errCode).": ".$arr[1];
		}

		$result = [
			'code' => $errCode,
			'msg'  => $msg,
		];

		$ec     = new \EC();
		$myCode = $ec->GetClassConstants();

		if (config("app_debug"))
		{// 开发模式
			$trace = [];

			$file = $e->getFile();
			//过滤tp5的help函数和自定义的E()
			if (strpos($file, "thinkphp/helper.php") === false && strpos($file, "app/common.php") === false)
			{
				$trace[] = $file."[{$e->getLine()}]"; //抛出异常的位置
			}

			$ts = $e->getTrace();
			foreach ($ts as $t)
			{
				if (isset($t["file"]) && isset($t['line']))
				{
					$trace[] = $t["file"]."[{$t['line']}]";
				}
			}
			$result["trace"] = $trace;

			if (!in_array($errCode, $myCode))
			{//非预期异常
				$result["msg"] = "API错误: ".$result["msg"];
			}

			if ($msg == \EC::PARAM_ERROR)
			{
				$result["msg"] .= "[请仔细核对API文档]";
			}
		}
		else
		{//生产模式
			//只显示自定义的异常信息
			if (!in_array($errCode, $myCode))
			{
				$result["msg"] = "程序出错,请联系管理员";
			}
		}

		//将0转为500
		if ($result["code"] === 0)
		{
			$result["code"] = \EC::API_ERR;
		}

		//返回错误信息
		if (Request::instance()
			->param("jsonp_callback/s")
		)
		{
			// ThinkPHP bug
			// Response类未处理jsonp类型 -- thinkphp/library/think/response/Jsonp.php
			// 当前台传递了 ['var_jsonp_handler' => 'jsonp_callback'] 参数时，表示是jsonp请求；否则按照 json 请求处理
			Response::create($result, "jsonp", \EC::SUCCESS, [], [
				'var_jsonp_handler' => Config::get('var_jsonp_handler'),
			])
				->send();
		}
		else
		{
			Response::create($result, "json", \EC::SUCCESS)
				->send();
		}
		exit();
	}
}