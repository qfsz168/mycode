<?php
/**
 * Created by PhpStorm.
 * User: WangChongquan
 * Date: 2017/2/16 0016
 * Time: 15:25
 */

namespace app\index\model;

class UploadChunkFiles
{
	/**
	 * GetFile
	 * form has two input type=file fields named "file1", "file2", etc
	 * @author 王崇全
	 * @date
	 * @return null
	 */
	public static function GetFile()
	{
		if (!isset($_FILES))
		{
			return null;
		}

		//取一个
		foreach ($_FILES as $sKey => $aFiles)
		{
			return $_FILES[$sKey];
		}

		return null;
	}

	/**
	 * UploadChunkFile
	 * @author 王崇全
	 * @date
	 * @param string $filename 文件名(如GUID)
	 * @param int  $chunk 分片号（从0开始）
	 * @param int  $chunks 分片数
	 * @param null $postData $postData 指定上传的数据块(不指定时，从$_FILES中获取)
	 * @param      $bFinishUploaded
	 * @param      $rfileName
	 * @param      $rfileSize
	 * @param      $rfilePath
	 * @return int
	 */
	public static function UploadChunkFile($filename, $chunk = 0, $chunks = 1, $postData = null, &$bFinishUploaded, &$rfileName, &$rfileSize, &$rfilePath)
	{
		// 执行时间延长至3min
		@set_time_limit(60 * 3);

		$file = self::GetFile();

		//如果没有分片的文件名,获取之
		if (empty($filename))
		{
			if (isset($_REQUEST["name"]))
			{ //从表单获取

				$filename = $_REQUEST["name"];
			}
			elseif (!empty($file))
			{ //从上传请求中获取

				$filename = $file["name"];
			}
			else
			{
				return \EC::SOURCE_NOT_EXIST_ERROR;
			}
		}

		//设置存储分片的路径
		$targetTempDir = self::GetTargetTempDir($filename);
		if (!file_exists($targetTempDir) && !@mkdir($targetTempDir))
		{
			return \EC::UPL_TMP_PATH;
		}

		$filePath = $targetTempDir.$filename;

		// 第几个分片(从0开始)
		if (!isset($chunk))
		{
			$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		}

		//总分片数
		if (!isset($chunks))
		{
			$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
		}

		// Remove old temp files
		self::CleanupTargetDir($targetTempDir, $filePath, $chunk);

		//临时文件分片名称
		$tmpThunkName = "{$filePath}_{$chunk}.parttmp";
		// 创建一个临时文件分片
		if (!$out = @fopen($tmpThunkName, "wb"))
		{
			return \EC::UPL_TMP_PATH;
		}

		//将分片写入到最终文件中
		if ($postData == null)
		{ //分片是来自上传

			//打开分片文件
			if (!empty($_FILES))
			{ //PHP能识别的文件,从$_FILES中获取

				//关闭文件, 直接复制
				@fclose($out);

				if ($file["error"] || !is_uploaded_file($file["tmp_name"]))
				{ //上传失败

					return \EC::UPL_THUNK_GETERR;
				}

				//复制临时文件
				move_uploaded_file($file["tmp_name"], $tmpThunkName);
			}
			else
			{ //PHP不能识别的文件,从php://input中获取

				if (!$in = @fopen("php://input", "rb"))//二进制
				{ //读取失败

					return \EC::UPL_THUNK_GETERR;
				}
				//以4K为单位写到临时分片文件中
				while ($buff = fread($in, 4096))
				{
					if ($buff)
					{
						if (!fwrite($out, $buff))
						{
							return \EC::UPL_TMPFILE_WRITE_ERR;
						}
					}
					else
					{
						return \EC::UPL_TMPFILE_READ_ERR;
					}
				}
				@fclose($in);
			}
		}
		else
		{ //分片是来自POST发送

			if (!fwrite($out, $postData))
			{
				return \EC::UPL_TMPFILE_WRITE_ERR;
			}
		}
		@fclose($out);

		rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");

		//检查是否所有分片都上传完成
		$done = true;
		for ($index = 0; $index < $chunks; $index++)
		{
			if (!file_exists("{$filePath}_{$index}.part"))
			{
				$done = false;
			}
		}

		//所有分片都上传完成
		if ($done)
		{
			$bFinishUploaded = false; //分片合成是否完毕的 标识
			//合成这些分片
			$rtn = self::FinishChunkFiles($filename, $chunks, $rfilePath);
			if (0 != $rtn)
			{
				return $rtn;
			}
			//分片合成完毕
			$bFinishUploaded = true;

			//文件大小
			if (file_exists($rfilePath))
			{
				$rfileSize = filesize($rfilePath);
			}

			//如果没有文件名,获取上传文件的文件名称
			if (empty($rfileName) && (!empty($_FILES)))
			{
				$rfileName = $file["name"];
			}

			if (!pathinfo($rfileName, PATHINFO_EXTENSION))
			{ //必须有扩展名
				return \EC::UPL_NO_FILE_NAME;
			}

		}

		return 0;
	}

	/**
	 * 分片数据合成
	 * @author 王崇全
	 * @date
	 * @param string $filename 文件名(如GUID)
	 * @param int    $chunks   分片数
	 * @param        $fileTarget
	 * @return int
	 */
	public static function FinishChunkFiles($filename, $chunks = 1, &$fileTarget)
	{
		if (empty($filename) || empty($chunks))
		{
			return \EC::PARAM_ERROR;
		}

		//获取存储分片的路径
		$targetTempDir = self::GetTargetTempDir($filename);
		if (!file_exists($targetTempDir))
		{
			return \EC::SOURCE_NOT_EXIST_ERROR;
		}

		//分片文件的公共名称
		$filePath = $targetTempDir.$filename;

		//最终文件的名称
		$fileTarget = dir_sub_date(DIR_FILES).DIRECTORY_SEPARATOR.$filename;

		if (file_exists($fileTarget))
		{ //如果文件存在(guid重复)

			return \EC::UPL_FILE_CREATE_ERR;
		}

		//确定总分片数
		if (!isset($chunks))
		{
			$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
		}

		//将各个分片合并
		for ($index = 0; $index < $chunks; $index++)
		{

			//以追加的方式打开$fileTarget文件
			if (!($out = @fopen($fileTarget, "ab")))
			{ //文件创建失败

				return \EC::UPL_FILE_CREATE_ERR;
			}

			if (flock($out, LOCK_EX))
			{
				if (!$in = @fopen("{$filePath}_{$index}.part", "rb"))
				{
					@fclose($out);

					return \EC::UPL_TMPFILE_READ_ERR;
				}

				while ($buff = fread($in, 4096))
				{
					if (!fwrite($out, $buff))
					{
						@fclose($in);
						@fclose($out);

						return \EC::UPL_THUNK_TO_FILE_ERR;
					}
				}

				@fclose($in);
			}
			flock($out, LOCK_UN);
			@fclose($out);
		}

		chmod($fileTarget, 0777);

		//删除临时文件夹
		//		BrowseFile::RmDirRec($targetTempDir);

		return 0;
	}

	/**
	 * 移除无效数据
	 * @author 王崇全
	 * @date
	 * @param $targetTempDir
	 * @param $filePath
	 * @param $chunk
	 * @return int
	 */
	private static function CleanupTargetDir($targetTempDir, $filePath, $chunk)
	{
		// 开关
		if (!FILEUPLOAD_CLEANUPTARGETDIR)
		{
			return 0;
		}

		//路径打开失败,或不存在
		if (!is_dir($targetTempDir) || !($dir = opendir($targetTempDir)))
		{
			return 0;
		}

		while (($file = readdir($dir)) !== false)
		{
			$tmpfilePath = $targetTempDir.DIRECTORY_SEPARATOR.$file;

			// 如果临时文件是当前分片，则不能删除
			if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp")
			{
				continue;
			}

			// 如果它是比最大年龄大的，并不是当前的文件，删除临时文件
			if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - FILEUPLOAD_MAXFILEAGE))
			{
				@unlink($tmpfilePath);
			}
		}

		closedir($dir);

		return 0;
	}

	/**
	 * 获取存储的临时文件夹路径,没有则创建之
	 * @param string $fid 文件编号
	 * @return bool|string $fid空,false; $fid有效,临时文件夹路径
	 */
	private static function GetTargetTempDir($fid)
	{
		if (!$fid)
		{
			return false;
		}

		$tmpPath = TmpFile::GetPath($fid);
		if (!$tmpPath)
		{
			//临时文件夹【fid -> path】必须是唯一的、独立的
			$now     = time();
			$tmpPath = DIR_TEMPS.date("Y", $now).DIRECTORY_SEPARATOR.date("m", $now).DIRECTORY_SEPARATOR.date("d", $now).DIRECTORY_SEPARATOR.date("H", $now).DIRECTORY_SEPARATOR.$fid.DIRECTORY_SEPARATOR;
			TmpFile::Add($fid, $tmpPath);
		}

		return $tmpPath;
	}
}