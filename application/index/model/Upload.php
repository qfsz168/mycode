<?php
namespace app\index\model;

class Upload
{
	/**
	 * 适配WebUploader的上传(最终的文件)
	 * @author 王崇全
	 * @date
	 * @param string $fid    文件UUID
	 * @param int    $chunks 总分片数
	 * @param int    $chunk  分片序号(从0起)
	 * @param string $md5    校验值(秒传用)
	 * @return array|string (分片成功,返回信息数组; 文件成功,返回文件的完整路径)
	 * @throws \Exception (有意外,抛出异常)
	 */
	public static function UploadWUL($fid, $chunks = 1, $chunk = 0, $md5 = null)
	{
		$file   = null;
		$result = [
			"fid"      => $fid,
			"finished" => 0,
			//整个文件的上传是否完成(秒传,是未完成)
		];

		//返回是否即将完成
		if ($chunk / $chunks >= 0.99 && $chunks >= 500)
		{
			$result['approachfinshed'] = FILE_SEC_PASS_AF_YES;
		}
		else
		{
			$result['approachfinshed'] = FILE_SEC_PASS_AF_NO;
		}

		// s1 检验是否秒传,并返回相应状态
		$canSecpass = false;
		if ($md5)
		{
			//查找hash对应的文件
			$file = FileHash::GetFile($md5);
			if (is_file($file))
			{
				$result['secpass'] = FILE_SEC_PASS_STATUS_OK;
				$canSecpass        = true;
			}
			else
			{
				$result['secpass'] = FILE_SEC_PASS_STATUS_NO;
			}
		}
		else
		{
			$result['secpass'] = FILE_SEC_PASS_STATUS_NOTGOT;
		}

		// s2 上传或秒传
		if (!$canSecpass)
		{ /*上传*/
			// s2.1 接收文件分片(尝试5次)
			$tmpFile = false;

			$i = 1;
			while (true)
			{
				if (($tmpFile = self::ReceiveChunkFile($fid, $chunk)) || ++$i > 5)
				{
					break;
				}
			}
			unset($i);

			if (!$tmpFile)
			{
				E(\EC::UPL_THUNK_GETERR);
			}

			//如果所有分片都上传完
			if (self::CheckAll($tmpFile, $chunks, $chunk))
			{
				// s2.2 分片合成总文件(尝试3次)
				$i = 1;
				while (true)
				{
					$file = self::Compose($fid, $tmpFile, $chunks);
					if ((is_file($file)) || ++$i > 3)
					{
						break;
					}
				}
				unset($i);

				if (!is_file($file))
				{
					E(\EC::UPL_THUNK_TO_FILE_ERR);
				}
			}

			//s2.3 整个文件上传完成后执行的操作
			if (is_file($file))
			{ //todo:fid必须是UUID,否则可能会把以前的文件当做是上传完成了的文件

				//保存文件的散列值，供秒传使用
				$hash = FileHash::CreateHash($file);
				if ($hash && !FileHash::GetFile($hash))
				{
					FileHash::Add($file, $hash);
				}

				Tmpfile::Add($fid, $file);

				$result["finished"] = 1;
			}
		}
		else
		{ /*秒传*/

			Tmpfile::Add($fid, $file);

			@unlink(dir_sub_date(DIR_FILES).$fid); //如果文件已经上传，删除之
		}

		// s3 上传或秒传完成后执行的操作
		if (is_file($file))
		{
			// 删除临时文件（夹）（秒传也会产生，所以放在此处）
			self::DelTmpDir($fid); //删除本次上传，fid之前已经上传的片段、临时文件
		}


		return $result;
	}

	/**
	 * 简单文件上传
	 * @author 王崇全
	 * @param string $fid      文件UUID
	 * @param array  $validate tp5上传的校验规则
	 * @return string 文件的完整路径
	 * @throws \Exception
	 */
	public static function UploadSimple($fid, $validate = [])
	{
		$files = Request()->file();
		$file  = @reset($files);
		if (!$file)
		{
			E(\EC::FILE_UPLOAD_ERROR);
		}

		$path = dir_sub_date(DIR_FILES);

		$fileInfo = $file->validate($validate)
			->move($path, $fid);
		if (!$fileInfo)
		{
			E(\EC::FILE_UPLOAD_ERROR, $file->getError());
		}

		$file     = $path.$fileInfo->getSaveName();
		$pathInfo = pathinfo($file);
		$newFile  = $pathInfo["dirname"].DIRECTORY_SEPARATOR.$pathInfo["filename"];
		if (!rename($file, $newFile))
		{
			E(\EC::FILE_UPLOAD_ERROR, "在上传后,去掉扩展名失败");
		}

		Tmpfile::Add($fid, $newFile);

		return $newFile;
	}

	/**
	 * 获取上传文件的路径
	 * @author 王崇全
	 * @param string $fid 文件UUID
	 * @return string 文件的完整路径
	 */
	public static function GetUploadFile(string $fid)
	{
		$tf = new Tmpfile();

		return $tf->where(["fid" => $fid])
			->cache(DEF_CACHE_TIME_SQL_SELECT)
			->value("path");
	}


	/**
	 * 接收一个分片文件
	 * @param  string $fileName 最终文件的basename（当前是fid）
	 * @param  int    $chunk    当前分片数（从0开始）
	 * @return bool|string 分片文件的全路径的公共部分 如：“/temp/fce900d3-6737-4331-9f6e-7fd5df044b26_0.part” 中的 “/temp/fce900d3-6737-4331-9f6e-7fd5df044b26”
	 */
	private static function ReceiveChunkFile($fileName, $chunk)
	{
		// 执行时间延长至10min
		@set_time_limit(60 * 10);

		// 获取存储分片的路径
		try
		{
			$tempDir = self::GetTempDir($fileName);
		}
		catch (\Exception $e)
		{
			return false;
		}
		if (!is_dir($tempDir))
		{
			return false;
		}

		$tmpFile = $tempDir.$fileName;

		// 移除当前分片之外的文件
		if (FILEUPLOAD_CLEANUPTARGETDIR)
		{
			self::CleanupTargetDir($tempDir, "{$tmpFile}_{$chunk}");
		}

		//若已存在删除之，以进行再次尝试
		$tmpThunkName = "{$tmpFile}_{$chunk}.parttmp"; //临时文件分片名称
		if (is_file($tmpThunkName))
		{
			@unlink($tmpThunkName);
		}

		//得到最终的分片文件
		if (!empty($_FILES))
		{
			//PHP能识别的文件,从$_FILES中获取
			if (!self::GetFile($tmpThunkName))
			{
				return false;
			}
		}
		else
		{
			//PHP不能识别的文件,从php://input中获取
			if (!copy("php://input", $tmpThunkName))
			{
				return false;
			}
		}

		if (!(rename($tmpThunkName, "{$tmpFile}_{$chunk}.part")))
		{
			return false;
		}

		return $tmpFile;
	}

	/**
	 * 判断所有分片是否都上传完 （上传并发数不能超过10）
	 * @param string $tmpFile 分片文件的全路径的公共部分 如：“/temp/fce900d3-6737-4331-9f6e-7fd5df044b26_0.part” 中的 “/temp/fce900d3-6737-4331-9f6e-7fd5df044b26”
	 * @param int    $chunks  总分片数
	 * @param int    $chunk   当前分片数（从0开始）
	 * @return bool
	 */
	private static function CheckAll($tmpFile, $chunks, $chunk)
	{
		//Todo: webUpload并发数不能超过10
		if ($chunks - $chunk > 10)
		{
			return false;
		}

		for ($index = 0; $index < $chunks; $index++)
		{
			if (!file_exists("{$tmpFile}_{$index}.part"))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * 将分片文件合并成最终文件
	 * @param string $fid     文件的UUID
	 * @param string $tmpFile 分片文件的全路径的公共部分 如：“/temp/fce900d3-6737-4331-9f6e-7fd5df044b26_0.part” 中的 “/temp/fce900d3-6737-4331-9f6e-7fd5df044b26”
	 * @param int    $chunks  总分片数
	 * @return bool|string 最终文件的全路径
	 */
	private static function Compose($fid, $tmpFile, $chunks)
	{
		//最终文件的名称
		$file = dir_sub_date(DIR_FILES).$fid;

		//如果文件存在，删除之，以进行再次尝试
		if (is_file($file))
		{
			@unlink($file);
		}

		try
		{
			//将分片文件的内容写入最终文件
			for ($index = 0; $index < $chunks; $index++)
			{

				//以追加的方式打开$fileTarget文件
				if (!($out = @fopen($file, "ab")))
				{ //文件创建失败

					return false;
				}

				if (flock($out, LOCK_EX))
				{
					if (!$in = @fopen("{$tmpFile}_{$index}.part", "rb"))
					{
						@fclose($out);

						return false;
					}

					while ($buff = fread($in, 4096))
					{
						if (!fwrite($out, $buff))
						{
							@fclose($in);
							@fclose($out);

							return false;
						}
					}

					@fclose($in);
				}
				flock($out, LOCK_UN);
				@fclose($out);
			}
		}
		catch (\Exception $e)
		{

			return false;
		}
		@chmod($file, 0777);

		return $file;
	}

	/**
	 * 删除分片文件的路径
	 * @author 王崇全
	 * @param $fid
	 * @return void
	 */
	private static function DelTmpDir($fid)
	{
		if ($fid)
		{
			$tmpDir = DIR_TEMPS.$fid.DIRECTORY_SEPARATOR;
			if (is_dir($tmpDir))
			{
				dir_del($tmpDir);
			}
		}
	}

	/**
	 * 获取第一个文件的上传信息
	 * @return bool|array
	 */
	private static function GetUploadInfo()
	{
		if (!isset($_FILES))
		{
			return false;
		}

		foreach ($_FILES as $k => $v)
		{
			return $_FILES[$k];
		}

		return false;
	}

	/**
	 * 获取存储分片文件的路径,没有则创建之
	 * @param string $fid 文件名（目前是guid）
	 * @return bool|string 临时文件夹路径
	 */
	private static function GetTempDir($fid)
	{
		//临时文件夹【fid -> path】必须是唯一的、独立的
		$tmpPath = DIR_TEMPS.$fid.DIRECTORY_SEPARATOR;
		if (is_dir($tmpPath))
		{
			return $tmpPath;
		}

		if (!mk_dir($tmpPath))
		{
			E(\EC::UPL_TMP_PATH);
		}

		return $tmpPath;
	}

	/**
	 * 移除无效数据(当前分片之外的文件)
	 * @param string $tempDir  临时文件的目录
	 * @param string $baseName 当前分片的全路径
	 * @return int $tempDir, $tmpFile, $chunk
	 */
	private static function CleanupTargetDir($tempDir, $baseName)
	{

		//路径打开失败,或不存在
		if (!is_dir($tempDir) || !($dir = opendir($tempDir)))
		{
			return false;
		}

		while (($file = readdir($dir)) !== false)
		{
			$tmpfilePath = $tempDir.DIRECTORY_SEPARATOR.$file;

			// 如果临时文件是当前分片，则不能删除
			if ($tmpfilePath == $baseName.'part' || $tmpfilePath == $baseName.'parttmp')
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
	 * 从$_FILES中获取文件
	 * @param string $file 目标文件的全路径
	 * @return bool
	 */
	private static function GetFile($file)
	{
		// 获取第一个文件的上传信息
		if (!($uploadInfo = self::GetUploadInfo()))
		{
			return false;
		}

		if ($uploadInfo["error"] || !is_uploaded_file($uploadInfo["tmp_name"]))
		{ //上传失败

			return false;
		}

		//复制临时文件
		move_uploaded_file($uploadInfo["tmp_name"], $file);

		return true;
	}
}