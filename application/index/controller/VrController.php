<?php
namespace app\api\controller;

use app\api\model\File;
use app\api\model\FileHash;
use app\api\model\FileVrHot;
use app\api\model\StreamRoomVrHot;
use app\api\model\TaskConvert;
use app\api\model\Tmpfile;
use think\Db;
use think\Exception;

class VrController extends BaseController
{
	/**
	 * 特写创建
	 * @author 王崇全
	 * @return array
	 */
	public function LCreate()
	{
		//**权限验证**
		$this->NeedToken();
		if (!$this->IsAdmin())
		{
			E(\EC::PERMISSION_NO_ERROR);
		}

		//**参数接收**
		$vali = $this->I([
			[
				"srid",
				null,
				"s",
				"require",
			],
			[
				"minx",
				null,
				"d",
				"require|>=:0",
			],
			[
				"miny",
				null,
				"d",
				"require|>=:0",
			],
			[
				"maxx",
				null,
				"d",
				"require|>=:0",
			],
			[
				"maxy",
				null,
				"d",
				"require|>=:0",
			],
			[
				"name",
				null,
				"s",
				"require",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$srvh   = new StreamRoomVrHot();
		$srvhId = $srvh->Add(self::$_input["srid"], self::$_input["name"], self::$_input["minx"], self::$_input["miny"], self::$_input["maxx"], self::$_input["maxy"]);

		//**数据返回**
		return $this->R(null, null, ["fid" => $srvhId]);
	}

	/**
	 * 修改创建
	 * @author 王崇全
	 * @return array
	 */
	public function LModify()
	{
		//**权限验证**
		$this->NeedToken();
		if (!$this->IsAdmin())
		{
			E(\EC::PERMISSION_NO_ERROR);
		}

		//**参数接收**
		$vali = $this->I([
			[
				"fid",
				null,
				"s",
				"require",
			],
			[
				"minx",
				null,
				"d",
				">=:0",
			],
			[
				"miny",
				null,
				"d",
				">=:0",
			],
			[
				"maxx",
				null,
				"d",
				">=:0",
			],
			[
				"maxy",
				null,
				"d",
				">=:0",
			],
			[
				"name",
				null,
				"s",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$srvh = new StreamRoomVrHot();
		$row  = $srvh->Edit(self::$_input["fid"], self::$_input["name"], self::$_input["minx"], self::$_input["miny"], self::$_input["maxx"], self::$_input["maxy"]);

		//**数据返回**
		return $this->R(null, null, ["modified" => $row]);
	}

	/**
	 * 删除热点
	 * @author 王崇全
	 * @return array
	 */
	public function LDelete()
	{
		//**权限验证**
		$this->NeedToken();
		if (!$this->IsAdmin())
		{
			E(\EC::PERMISSION_NO_ERROR);
		}

		//**参数接收**
		$vali = $this->I([
			[
				"fids",
				null,
				"s",
			],
			//热点编号列表
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$fhotids = ids2array(self::$_input["fids"]);

		$rows = 0;
		$srvh = new StreamRoomVrHot();
		Db::startTrans();
		try
		{
			foreach ($fhotids as $fhotid)
			{
				if (!$srvh->GetInfo($fhotid))
				{
					E(\EC::VR_HOT_NOT_EXIST);
				}
				$rows += $srvh->Del($fhotid);
			}

			Db::commit();
		}
		catch (Exception|\Exception $e)
		{
			Db::rollback();
			E($e->getCode(), $e->getMessage());
		}

		//**数据返回**
		return $this->R(null, null, ["deleted" => $rows]);
	}

	/**
	 * 获取热点信息
	 * @author 王崇全
	 * @return array
	 */
	public function LGetInfo()
	{
		//**权限验证**
		$this->NeedToken();
		if (!$this->IsAdmin())
		{
			E(\EC::PERMISSION_NO_ERROR);
		}

		//**参数接收**
		$vali = $this->I([
			[
				"fid",
				null,
				"s",
				"require",
			],
			[
				"with_play_info",
				NO,
				"d",
				"in:".NO.",".YES,
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$srvh = new StreamRoomVrHot();
		$info = $srvh->GetInfo(self::$_input["fid"], self::$_input["with_play_info"]);

		//**数据返回**
		return $this->R(null, null, $info);
	}

	/**
	 * 热点信息列表
	 * @author 王崇全
	 * @return array
	 */
	public function LGetList()
	{
		//**权限验证**
		$this->NeedToken();
		if (!$this->IsAdmin())
		{
			E(\EC::PERMISSION_NO_ERROR);
		}

		//**参数接收**
		$vali = $this->I([
			[
				"srid",
				null,
				"s",
				"require",
			],
			[
				"with_play_info",
				NO,
				"d",
				"in:".NO.",".YES,
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$srvh = new StreamRoomVrHot();
		$list = $srvh->GetList(self::$_input["srid"], self::$_input["with_play_info"]);

		//**数据返回**
		return $this->R(null, null, $list, count($list));
	}


	/**
	 * 文件特写创建
	 * @author 王崇全
	 * @return array
	 */
	public function FCreate()
	{
		//**权限验证**
		$this->NeedToken();

		//**参数接收**
		$vali = $this->I([
			[
				"pfid",
				null,
				"s",
				"require",
			],
			[
				"name",
				null,
				"s",
				"require",
			],
			[
				"file_id",
				null,
				"s",
				"require",
			],
			[
				"file_type",
				null,
				"d",
				"require|in:".FileVrHot::TYPE_FILE_OD,
			],
			[
				"new_file_name",
				null,
				"s",
			],
			[
				"minx",
				null,
				"d",
				"require|>=:0",
			],
			[
				"miny",
				null,
				"d",
				"require|>=:0",
			],
			[
				"maxx",
				null,
				"d",
				"require|>=:0",
			],
			[
				"maxy",
				null,
				"d",
				"require|>=:0",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**

		//只有自己的文件才可以添加特写
		$f      = new File();
		$pfInfo = $f->GetInfo(self::$_input["pfid"], false);
		if ($pfInfo["userid"] != self::$_uid)
		{
			E(\EC::PERMISSION_NO_ERROR);
		}

		//获取文件
		$file = null;
		if (self::$_input["file_type"] == FileVrHot::TYPE_FILE_UPL)
		{
			if (!self::$_input["new_file_name"])
			{
				E(\EC::PARAM_ERROR, "请提供新上传文件的文件名");
			}

			Db::startTrans();
			try
			{
				$file = Tmpfile::GetPath(self::$_input["file_id"]);
				if (!FileHash::CreateHash($file) || !File::CheckExist($file))
				{
					//1.1 将H264文件的文件头移动到文件尾
					File::H264Isma($file);

					//1.2 写入待转码任务
					TaskConvert::AddConvert($file, self::$_input["new_file_name"]);
				}

				Db::commit();
			}
			catch (Exception|\Exception $e)
			{
				Db::rollback();
				E($e->getCode(), $e->getMessage());
			}


		}
		elseif (self::$_input["file_type"] == FileVrHot::TYPE_FILE_OD)
		{
			$fInfo1 = $f->GetInfo(self::$_input["file_id"], false);
			if (!$fInfo1)
			{
				E(\EC::SOURCE_NOT_EXIST_ERROR);
			}
			$file = $fInfo1["path"];
		}
		else
		{
			E(\EC::PARAM_ERROR);
		}

		$fvh   = new FileVrHot();
		$fvrid = $fvh->Add(self::$_input["pfid"], self::$_input["name"], $file, self::$_input["minx"], self::$_input["miny"], self::$_input["maxx"], self::$_input["maxy"]);

		//**数据返回**
		return $this->R(null, null, ["fvrid" => $fvrid]);
	}

	/**
	 * 文件特写修改
	 * @author 王崇全
	 * @return array
	 */
	public function FModify()
	{
		//**权限验证**
		$this->NeedToken();

		//**参数接收**
		$vali = $this->I([
			[
				"fvrid",
				null,
				"s",
				"require",
			],
			[
				"name",
				null,
				"s",
			],
			[
				"file_id",
				null,
				"s",
			],
			[
				"file_type",
				null,
				"d",
				"in:".FileVrHot::TYPE_FILE_OD,
			],
			[
				"new_file_name",
				null,
				"s",
			],
			[
				"minx",
				null,
				"d",
				">=:0",
			],
			[
				"miny",
				null,
				"d",
				">=:0",
			],
			[
				"maxx",
				null,
				"d",
				">=:0",
			],
			[
				"maxy",
				null,
				"d",
				">=:0",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**

		//获取文件
		if (isset(self::$_input["file_id"]) && (self::$_input["file_type"] == FileVrHot::TYPE_FILE_UPL))
		{
			if (!self::$_input["new_file_name"])
			{
				E(\EC::PARAM_ERROR, "请提供新上传文件的文件名");
			}

			$file = Tmpfile::GetPath(self::$_input["file_id"]);

			//1.1 将H264文件的文件头移动到文件尾
			File::H264Isma($file);

			//1.2 写入待转码任务
			TaskConvert::AddConvert($file, self::$_input["new_file_name"]);

		}
		elseif (isset(self::$_input["file_id"]) && (self::$_input["file_type"] == FileVrHot::TYPE_FILE_OD))
		{
			$f     = new File();
			$fInfo = $f->GetInfo(self::$_input["file_id"], false);
			if (!$fInfo)
			{
				E(\EC::SOURCE_NOT_EXIST_ERROR);
			}
			$file = $fInfo["path"];
		}
		else
		{
			$file = null;
		}

		$fvh   = new FileVrHot();
		$fvrid = $fvh->Edit(self::$_input["fvrid"], self::$_input["name"], $file, self::$_input["minx"], self::$_input["miny"], self::$_input["maxx"], self::$_input["maxy"]);

		//**数据返回**
		return $this->R(null, null, ["fvrid" => $fvrid]);
	}

	/**
	 *  文件特写删除
	 * @author 王崇全
	 * @return array
	 */
	public function FDelete()
	{
		//**权限验证**
		$this->NeedToken();

		//**参数接收**
		$vali = $this->I([
			[
				"fvrids",
				null,
				"s",
			],
			//热点编号列表
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$fhotids = ids2array(self::$_input["fvrids"]);

		$rows = 0;
		$srvh = new StreamRoomVrHot();
		Db::startTrans();
		try
		{
			foreach ($fhotids as $fhotid)
			{
				$rows += $srvh->Del($fhotid);
			}

			Db::commit();
		}
		catch (Exception|\Exception $e)
		{
			Db::rollback();
			E($e->getCode(), $e->getMessage());
		}

		//**数据返回**
		return $this->R(null, null, ["deleted" => $rows]);
	}

	/**
	 * 获取文件特写信息
	 * @author 王崇全
	 * @return array
	 */
	public function FGetInfo()
	{
		//**权限验证**
		$this->NeedToken();

		//**参数接收**
		$vali = $this->I([
			[
				"fvrid",
				null,
				"s",
				"require",
			],
			[
				"with_play_info",
				NO,
				"d",
				"in:".NO.",".YES,
			],
			[
				"definition",
				File::RESOLUTION_LOW,
				"d",
				"in:".File::SET_RESOLUTION,
			],
			[
				"page",
				DEF_PAGE,
				"d",
				">:0",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$fvh  = new FileVrHot();
		$info = $fvh->GetInfo(self::$_input["fvrid"], self::$_input["with_play_info"], self::$_input["page"], self::$_input["definition"]);

		//**数据返回**
		return $this->R(null, null, $info);
	}

	/**
	 * 文件特写列表
	 * @author 王崇全
	 * @return array
	 */
	public function FGetList()
	{
		//**权限验证**
		$this->NeedToken();

		//**参数接收**
		$vali = $this->I([
			[
				"pfid",
				null,
				"s",
				"require",
			],
			[
				"page",
				DEF_PAGE,
				"d",
				">:0",
			],
			[
				"perpage",
				DEF_PAGE_SIZE,
				"d",
				">:0",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		$fvh  = new FileVrHot();
		$list = $fvh->GetList(self::$_input["pfid"], self::$_input["page"], self::$_input["perpage"]);

		//**数据返回**
		return $this->R(null, null, @$list[0], @$list[1]);
	}
}