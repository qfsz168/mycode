<?php

namespace app\index\controller;

use think\Cache;
use think\Config;
use think\Cookie;
use think\Request;
use think\Response;
use think\Controller;

use think\Session;

/**
 * 公共类
 * @package app\index\controller
 */
class BaseController extends Controller
{
	protected static $_input = null; //请求参数
	protected static $_uinfo = null; //用户编号
	protected static $_uid   = null; //用户信息
	protected static $_token = null; //用户令牌


	/**
	 * 构造器
	 * @author 王崇全
	 * @date
	 * @return void
	 */
	public function _initialize()
	{
		// 跨域向IE8写入cookie
		if (IE8)
		{
			// P3P header允许跨域访问隐私数据
			header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
		}
	}

	/**
	 * 操作方法容错
	 * @author 王崇全
	 * @date
	 * @param Request $request
	 * @return array
	 */
	public function _empty(Request $request)
	{
		return $this->R(\EC::URL_ERROR, strtolower(" ".$request->domain()."/".$request->module()."/".$request->controller()."/".$request->action()." 不存在"), "请求地址错误");
	}

	/**
	 * @var array 先行方法列表
	 */
	protected $beforeActionList = ['GetUserInfo',];


	/**
	 * 重写 验证数据 方法
	 * @access protected
	 * @param array        $data     数据
	 * @param string|array $validate 验证器名或者验证规则数组
	 * @param array        $message  提示信息
	 * @param bool         $batch    是否批量验证
	 * @param mixed        $callback 回调方法（闭包）
	 * @return array|string|true
	 */
	protected function validate($data, $validate, $message = [], $batch = true, $callback = null)
	{
		return parent::validate($data, $validate, $message, $batch, $callback);
	}

	/**
	 * 接收并校验参数,并将验证后的参数保存在self::$_input中
	 * @author 王崇全
	 * @param array $paramsInfo 参数列表 四项依次为:参数名,默认值,tp的强制转换类型(s,d,a,f,b),tp的验证规则
	 *                          eg:[
	 *                          [ "age",  18, "d",  "number|<=:150|>:0"],
	 *                          [ "sex",  null, "s",  "require"],
	 *                          ]
	 * @return array|string|true
	 */
	protected function I($paramsInfo)
	{
		//数据接收&校验
		$request = Request::instance();

		$toVali = false;
		$params = $rule = [];
		foreach ($paramsInfo as $paramInfo)
		{
			$paramInfo[0] = $paramInfo[0]??null;
			$paramInfo[1] = $paramInfo[1]??null;
			$paramInfo[2] = $paramInfo[2]??null;
			$paramInfo[3] = $paramInfo[3]??null;

			if (!is_array($paramInfo) || !$paramInfo[0])
			{
				continue;
			}

			//$parmInfo[0] 参数名
			$paramName = "{$paramInfo[0]}";

			//$parmInfo[2] tp的强制转换类型
			if (in_array($paramInfo[2], [
				"s",
				"d",
				"b",
				"a",
				"f",
			]))
			{
				$paramName .= "/{$paramInfo[2]}";
			}

			//$parmInfo[1] 默认值
			if (isset($paramInfo[1]))
			{
				$params[$paramInfo[0]] = $request->param($paramName, $paramInfo[1]);
			}
			else
			{
				$params[$paramInfo[0]] = $request->param($paramName);
			}

			//$parmInfo[3] tp的验证规则
			if (is_string($paramInfo[3]))
			{
				$rule[$paramInfo[0]] = $paramInfo[3];
				$toVali              = true;
			}

		}

		self::$_input = $params;

		if ($toVali)
		{
			return $this->validate(self::$_input, $rule);
		}
		else
		{
			return true;
		}
	}

	/**
	 * 返回请求结果(0不表示成功)
	 * @author 王崇全
	 * @param int    $code  错误码 (成功用200|null表示)
	 * @param array  $data  返回的数据
	 * @param int    $count 用在list列表,表示符合条件的总数.(注:不是count($data))
	 * @param int    $took  耗时（以毫秒为单位）
	 * @param string $msg   自定义的错误信息
	 * @return array
	 */
	protected function R($code = 200, $msg = "", $data = null, $count = null, $took = null)
	{
		//Todo:临时方案，待优化(考虑到安全，目前不会返回userid。以后如果需，可将本段代码移至控制器的操作方法中)
		if (is_array($data))
		{
			array_del_key($data, "userid");
		}

		if (is_null($code) || (0 == $code))
		{
			$code = \EC::SUCCESS;
		}
		if (!$msg)
		{
			$msg = \EC::GetMsg($code);
		}
		$r = [
			"code" => $code,
			"msg"  => $msg,
		];

		if (isset($count))
		{
			$r["count"] = $count;
		}
		if (isset($took))
		{
			$r["took"] = $took;
		}
		if (isset($data))
		{
			$r["result"] = $data;
		}

		if (Request::instance()
			->param("jsonp_callback/s")
		)
		{
			// ThinkPHP bug
			// Response类未处理jsonp类型 -- thinkphp/library/think/response/Jsonp.php
			// 当前台传递了 ['var_jsonp_handler' => 'jsonp_callback'] 参数时，表示是jsonp请求；否则按照 json 请求处理
			Response::create($r, "jsonp", \EC::SUCCESS, [], [
				'var_jsonp_handler' => Config::get('var_jsonp_handler'),
			])
				->send();
		}
		else
		{
			Response::create($r, "json", \EC::SUCCESS)
				->send();
		}
		exit();
	}

	/**
	 * 先行方法 - 验证令牌 并 获取&防止异地登陆$保存用户信息
	 * @author 王崇全
	 * @date
	 * @return void
	 */
	protected function GetUserInfo()
	{
		$request = Request::instance();

		self::$_token = $request->param("ACCESS-TOKEN/s");
		if (!self::$_token)
		{
			self::$_token = null;
		}

		$ip = $request->ip();
		$ua = $request->header("user-agent");

		if (isset(self::$_token))
		{ //用户请求

			//s1 获取userid并缓存
			$aot = new ApiOauthTokens();
			$uid = $aot->GetUserid(self::$_token);
			if (is_null($uid))
			{
				Response::create($this->R(\EC::ACCESSTOKEN_ERROR), "json")
					->send();
				die();
			}

			self::$_uid = $uid;//供控制器使用
			Session::set("user_id", $uid);//供模型使用

			//s2 防止多登录(前提,每个终端的令牌都不一样)
			$cacheKeyUserLoginPCInfo = CACHE_PREFIX_USER_LOGIN_PC_INFO.$uid;

			$userLoginPCInfo = Cache::get($cacheKeyUserLoginPCInfo);
			if ($userLoginPCInfo === false)
			{ //未登录过
				Cache::set($cacheKeyUserLoginPCInfo, [
					$ip,
					$ua,
				], 0);
			}
			else
			{ //登录过
				if ($ip != $userLoginPCInfo[0] || $ua != $userLoginPCInfo[1])
				{ //非同一终端

					//剔除其他终端
					$aot    = new ApiOauthTokens();
					$tokens = $aot->GetOtherTokens($uid, self::$_token);
					if (is_array($tokens))
					{
						foreach ($tokens as $token)
						{
							Cache::rm(CACHE_PREFIX_T2U.$token);
							$aot->DelByToken($token);
						}
					}
				}
			}

			//s3 获取用户信息并缓存
			$user     = new User();
			$userInfo = $user->GetInfo($uid);
			if (!$user)
			{
				Response::create($this->R(\EC::USER_NOTEXIST_ERROR), "json")
					->send();
				die();
			}

			$userInfo["ip"] = $ip;
			$userInfo["ua"] = $ua;

			self::$_uinfo = $userInfo;//供控制器使用
			Session::set("user_info", $userInfo);//供模型使用
		}
		else
		{ //匿名用户请求
			if (!(Cookie::get("uic")))
			{
				Cookie::set("uic", make_uic(), 3600 * 24 * 365 * 10);
			}
		}
	}

	/**
	 * 身份识别 - 必有有令牌
	 * @author 王崇全
	 * @date
	 * @return void
	 */
	protected function NeedToken()
	{
		if (is_null(self::$_token))
		{
			$r = [
				"code" => \EC::URL_ACCESSTOKEN_NOTEXIST_ERROR,
				"msg"  => "缺少令牌",
			];
			if (Request::instance()
				->param("jsonp_callback/s")
			)
			{
				// ThinkPHP bug
				// Response类未处理jsonp类型 -- thinkphp/library/think/response/Jsonp.php
				// 当前台传递了 ['var_jsonp_handler' => 'jsonp_callback'] 参数时，表示是jsonp请求；否则按照 json 请求处理
				Response::create($r, "jsonp", \EC::SUCCESS, [], ['var_jsonp_handler' => Config::get('var_jsonp_handler')])
					->send();
			}
			else
			{
				Response::create($r, "json", \EC::SUCCESS)
					->send();
			}
			exit();
		}
	}

	/**
	 * 身份识别 - 是否是管理员
	 * @author 王崇全
	 * @date
	 * @return bool
	 */
//	protected function IsAdmin()
//	{
//		if (is_null(self::$_uinfo))
//		{
//			return false;
//		}
//
//		if (@self::$_uinfo["name"] == "admin")
//		{
//			return true;
//		}
//
//		/*$role  = new Role();
//		$rinfo = $role->GetInfo(@self::$_uinfo["roleid"], "r");
//		if ($rinfo["type"] == Role::TYPE_ADMIN)
//		{
//			return true;
//		}*/
//
//		return false;
//	}
//
//
//	protected function RmCacheUserInfo()
//	{
//		$cacheKeyUser = CACHE_PREFIX_USER_INFO.self::$_uid;
//		Cache::rm($cacheKeyUser);
//	}
}