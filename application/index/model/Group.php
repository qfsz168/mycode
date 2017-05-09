<?php

namespace app\api\model;

use app\index\model\Base;
use think\Db;
use traits\model\SoftDelete;

class Group extends Base
{
	//软删除
	use SoftDelete;
	protected $deleteTime = 'delete_time';

	//自动时间
	protected $autoWriteTimestamp = "int";
	protected $updateTime         = false;

	protected function initialize()
	{
		parent::initialize();
	}

	protected function getIsLeafNodeAttr($value, $data)
	{
		return $data["rightcode"] - $data["leftcode"] == 1 ? YES : NO;
	}

	/**
	 * 添加分组
	 * @author 王崇全
	 * @date
	 * @param string      $name        组名
	 * @param string|null $pgid        父组ID
	 * @param string|null $cover       封面文件路径
	 * @param string|null $description 组简介
	 * @return mixed|string 组ID
	 */
	public function Add(string $name, string $pgid = null, string $cover = null, string $description = null)
	{
		if (!$pgid)
		{
			$pgRightCode = self::max("leftcode") + 1;
		}
		else
		{
			$pgRightCode = self::where(["groupid" => $pgid])
				->value("rightcode");
		}
		if (!$pgRightCode)
		{
			E(\EC::PARAM_ERROR, "此父组不存在");
		}

		$newGroupId = guid();

		Db::startTrans();
		try
		{
			self::where("leftcode", ">=", $pgRightCode)
				->setInc("leftcode", 2);
			self::where("rightcode", ">=", $pgRightCode)
				->setInc("rightcode", 2);

			$new = self::create([
				"imgpath"     => $cover??"",
				"leftcode"    => $pgRightCode,
				"rightcode"   => $pgRightCode + 1,
				"parentid"    => $pgid??"",
				"name"        => $name??"",
				"description" => $description??"",
				"userid"      => $this->GetUid(),
				"groupid"     => $newGroupId,
			]);
			if (!$new)
			{
				E(\EC::API_ERR, "分组添加失败");
			}

			$newGroupId = $new->getAttr("groupid");

			Db::commit();
		}
		catch (\Exception $e)
		{
			Db::rollback();
			E($e->getCode(), $e->getMessage());
		}

		return $newGroupId;
	}

	/**
	 * 删除分组
	 * @author 王崇全
	 * @date
	 * @param string $gid
	 * @return int
	 */
	public function Del(string $gid)
	{
		$info = self::field("leftcode,rightcode,rightcode-leftcode+1 AS width")
			->where("groupid", $gid)
			->find();
		if (!$info)
		{
			E(\EC::API_ERR, "此分组不存在");
		}

		$lcode = $info->getAttr("leftcode");
		$rcode = $info->getAttr("rightcode");
		$width = $info->getAttr("width");

		$rows = 0;
		Db::startTrans();
		try
		{
			$rows = self::destroy([
				"leftcode" => [
					"between",
					[
						$lcode,
						$rcode,
					],
				],
			]);

			self::where("leftcode", ">", $lcode)
				->setDec("leftcode", $width);
			self::where("rightcode", ">", $rcode)
				->setDec("rightcode", $width);

			Db::commit();
		}
		catch (\Exception $e)
		{
			Db::rollback();
			E($e->getCode(), $e->getMessage());
		}

		return $rows;
	}

	/**
	 * 移动分组
	 * @author 王崇全
	 * @date
	 * @param string $gid 分组ID
	 * @param string $pos 目标位置的分组ID
	 * @return void
	 */
	public function MoveTo(string $gid, string $pos)
	{
		if ($gid == $pos)
		{
			return;
		}
		$info = self::field("parentid,leftcode,rightcode,rightcode-leftcode+1 AS width")
			->where(["groupid" => $gid])
			->find();
		if (!$info)
		{
			E(\EC::API_ERR, "此分组不存在");
		}

		$infoBeforeGroup = self::field("parentid,leftcode,rightcode,rightcode-leftcode+1 AS width")
			->where(["groupid" => $pos])
			->find();
		if (!$infoBeforeGroup)
		{
			E(\EC::API_ERR, "目标位置分组不存在");
		}

		if ($info["parentid"] != $infoBeforeGroup["parentid"])
		{
			E(\EC::API_ERR, "只能平级移动");
		}

		$distance = $info["leftcode"] - $infoBeforeGroup["leftcode"];

		//是否是向左移动
		$toLeft = $distance > 0 ? true : false;

		//获取此节点的所有子节点
		$groups = self::where([
			"leftcode" => [
				"between",
				[
					$info["leftcode"],
					$info["rightcode"],
				],
			],
		])
			->column("id");


		Db::startTrans();
		try
		{

			//移动分组
			if ($toLeft)
			{
				//s1 顺移其他分组
				self::where("leftcode", ">=", $infoBeforeGroup["leftcode"])
					->where("leftcode", "<", $info["leftcode"])
					->where("delete_time", "exp", "IS NULL")
					->setInc("leftcode", $info["width"]);

				self::where("rightcode", ">", $infoBeforeGroup["leftcode"])
					->where("rightcode", "<", $info["leftcode"])
					->where("delete_time", "exp", "IS NULL")
					->setInc("rightcode", $info["width"]);

				//s2 移动本分组
				self::where([
					"id" => [
						"IN",
						$groups,
					],
				])
					->where("delete_time", "exp", "IS NULL")
					->setDec("leftcode", $distance);

				self::where([
					"id" => [
						"IN",
						$groups,
					],
				])
					->where("delete_time", "exp", "IS NULL")
					->setDec("rightcode", $distance);
			}
			else
			{
				$distance = $infoBeforeGroup["rightcode"] - $info["rightcode"];

				//s1 顺移其他分组
				self::where("leftcode", "<", $infoBeforeGroup["rightcode"])
					->where("leftcode", ">", $info["rightcode"])
					->where("delete_time", "exp", "IS NULL")
					->setDec("leftcode", $info["width"]);

				self::where("rightcode", ">", $info["rightcode"])
					->where("rightcode", "<=", $infoBeforeGroup["rightcode"])
					->where("delete_time", "exp", "IS NULL")
					->setDec("rightcode", $info["width"]);

				//s2 移动本分组
				self::where([
					"id" => [
						"IN",
						$groups,
					],
				])
					->where("delete_time", "exp", "IS NULL")
					->setInc("leftcode", $distance);

				self::where([
					"id" => [
						"IN",
						$groups,
					],
				])
					->where("delete_time", "exp", "IS NULL")
					->setInc("rightcode", $distance);
			}

			Db::commit();
		}
		catch (\Exception $e)
		{
			Db::rollback();
			E($e->getCode(), $e->getMessage());
		}
	}

	/**
	 * 分组列表
	 * @author 王崇全
	 * @date
	 * @param string|null $pgid    父组ID
	 * @param bool        $onlySon 仅包括直接子节点
	 * @return array
	 */
	public function GetList(string $pgid = null, bool $onlySon = true)
	{
		$exceptField = [
			"id",
			"delete_time",
		];

		$map = [];

		//仅包括直接子节点
		if ($onlySon)
		{
			if (isset($pgid))
			{
				$map["parentid"] = $pgid;
			}
			else
			{
				$map["parentid"] = "";
			}
		}
		else
		{
			//不仅包括直接子节点
			if (isset($pgid))
			{
				$info = self::field([
					"leftcode",
					"rightcode",
				])
					->where(["groupid" => $pgid])
					->find();
				if (!$info)
				{
					return [];
				}

				$map["leftcode"]  = [
					">",
					$info["leftcode"],
				];
				$map["rightcode"] = [
					"<",
					$info["rightcode"],
				];
			}
		}

		$list = $this->field($exceptField, true)
			->where($map)
			->cache(DEF_CACHE_TIME_SQL_SELECT)
			->order(["leftcode"])
			->select();
		if (!$list)
		{
			return [];
		}

		$hidden = [];
		if ($onlySon)
		{
			$hidden = [
				"leftcode",
				"rightcode",
			];
		}

		return $this->ToArr($list, $hidden, ["is_leaf_node"]);
	}


	/**
	 * 数组转树-非递归
	 * @author 王崇全
	 * @date
	 * @param array  $tree
	 * @param string $pid
	 * @return array
	 */
	public function GetTree(array $tree, string $pid)
	{
		//目标数组
		$rest = [];

		//索引数组，用于记录节点在目标数组的位置
		$index = [];

		foreach ($tree as $node)
		{
			//给每个节点附加一个child项
			$node["child"] = [];
			unset($node["leftcode"]);
			unset($node["rightcode"]);

			if ($node["parentid"] == $pid)
			{
				$i = count($rest);

				$rest[$i] = $node;

				$index[$node["groupid"]] =& $rest[$i];
			}
			else
			{
				$i = count($index[$node["parentid"]]["child"]);

				$index[$node["parentid"]]["child"][$i] = $node;

				$index[$node["groupid"]] =& $index[$node["parentid"]]["child"][$i];
			}
		}

		return $rest;
	}

	/**
	 * 数组转树-递归
	 * @author 王崇全
	 * @date
	 * @param $array
	 * @param $pid
	 * @return array
	 */
	public function GetTreeRecursion($array, $pid)
	{
		$tree = [];
		foreach ($array as $v)
		{
			if ($v['parentid'] == $pid)
			{
				//父亲找到儿子
				$v['child'] = $this->GetTreeRecursion($array, $v['groupid']);
				$tree[]     = $v;
			}
		}

		return $tree;
	}

}