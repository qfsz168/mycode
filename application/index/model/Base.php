<?php
/**
 * Created by PhpStorm.
 * User: WangChongquan
 * Date: 2017/1/24 0024
 * Time: 10:32
 */

namespace app\index\model;

use think\Model;
use think\db\Query;
use think\Session;

/**
 * Class Base
 * @package app\index\model
 * @property User $_user User类实例
 */
class Base extends Model
{
	protected $_user = null; //user实例

	protected function initialize()
	{
		parent::initialize();

		//因为user太常用, 故将User类实例化, 避免在子类中多次重复实例化User
		$this->_user = new User();
	}

	//获取器-用户昵称
	protected function getNickNameAttr($value, $data)
	{
		if ($data["userid"] == User::UID_SYS)
		{
			return "系统";
		}
		elseif ($data["userid"] == User::UID_ANON)
		{
			return "匿名";
		}

		$u     = $this->_user;
		$uinfo = $u->GetInfo($data["userid"]);
		if (!$uinfo)
		{
			return "";
		}

		return $uinfo["nickname"];
	}

	//获取器-用户名
	protected function getUserNameAttr($value, $data)
	{
		if ($data["userid"] == User::UID_SYS)
		{
			return "系统";
		}
		elseif ($data["userid"] == User::UID_ANON)
		{
			return "匿名";
		}

		return $this->_user->GetUserName($data["userid"])??"";
	}

	//获取器-stats_info
	protected function getStatsInfoAttr($value, $data)
	{
		$sid = $data["to_stats"];

		$ss     = new SourceStats();
		$ssInfo = $ss->GetInfo($sid);
		if (!$ssInfo)
		{
			return [];
		}
		unset($ssInfo["update_time"]);

		return $ssInfo;
	}


	/**
	 * 将由模型组成的数组转化为纯属组
	 * @author 王崇全
	 * @param array $data   由模型组成的数组
	 * @param array $hidden 要隐藏的属性
	 * @param array $append 通过拾取器添加的属性
	 * @return array
	 */
	protected function ToArr($data, $hidden = [], $append = [])
	{
		if (!$data)
		{
			return [];
		}

		$arr = [];
		foreach ($data as $val)
		{
			$arr[] = $val->append($append ? $append : [])
				->hidden($hidden ? $hidden : [])
				->toArray();
		}

		return $arr;
	}

	/**
	 * 获取用户编号
	 * @author 王崇全
	 * @date
	 * @return mixed
	 */
	protected function GetUid()
	{
		return Session::get("user_id");
	}

	/**
	 * 获取用户信息
	 * @author 王崇全
	 * @date
	 * @return mixed
	 */
	protected function GetUinfo()
	{
		return Session::get("user_info");
	}

	/**
	 * 判断是否是管理员
	 * @author 王崇全
	 * @date
	 * @return bool
	 */
	protected function IsAdmin()
	{
		//超级管理员
		$userInfo = $this->GetUinfo();
		if ($userInfo["name"] == "admin")
		{
			return true;
		}

		return false;
	}

}