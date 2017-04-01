<?php
namespace app\index\model;

use traits\model\SoftDelete;

class Test extends Base
{
	//type(直播类型)
	const TYPE_LIVE        = 1; //直播
	const TYPE_INTERACTION = 2; //互动
	const SET_TYPE         = self::TYPE_LIVE.",".self::TYPE_INTERACTION;
	const ARRAY_TYPE       = [
		self::TYPE_LIVE,
		self::TYPE_INTERACTION,
	];

	//软删除
	use SoftDelete;
	protected $deleteTime = 'delete_time';

	//只读字段
	//	protected $readonly = ['name', 'email'];

	//自动时间
	protected $autoWriteTimestamp = "int";
	protected $updateTime         = false;

	//自动完成
	//	protected $update = ["update_time"];
	//	protected $auto = ["update_time"];

	protected $insert = ['msgid'];

	//修改器-消息编号
	protected function setMsgidAttr()
	{
		return guid();
	}

	//日期时间格式
	protected $dateFormat = "Y-m-d H:i:s";

	//自动类型转换
	protected $type = [
		"create_time" => "int",
		"update_time" => "int",
	];

	//修改器-验证 audit_status 合法性
	protected function setAuditSatausAttr($value)
	{
		if (!in_array($value, self::ARRAY_AUDIT_STATUS))
		{
			E(\EC::PARAM_ERROR);
		}

		return $value;
	}

	//获取器-用户名
	protected function getUserNameAttr($value, $data)
	{
		$u     = $this->_user;
		$uinfo = $u->GetInfo($data["userid"]);
		if (!$uinfo)
		{
			return "";
		}

		return $uinfo["nickname"] ? $uinfo["nickname"] : $uinfo["name"];
	}

}