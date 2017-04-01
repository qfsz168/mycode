<?php
namespace http;

class ApacheXSendFile
{
	/**
	 * 向浏览器输出文件
	 * @param string $path     文件的全路径 eg: /mnt/volume1/files/2016/9/26/12/3e883ca8-154a-4c1c-b6ce-3fe93e6ead07_thumb_400_300.jpg
	 * @param string $fileName 显示的名称 eg: 道德经的奥秘.mp4
	 * @return int
	 */
	public static function Download($path, $fileName = null)
	{
		if (!isset($path) || !is_file($path))
		{
			E(\EC::SOURCE_NOT_EXIST_ERROR, "文件不存在");
		}

		if (is_null($fileName))
		{
			$fileName = basename($path);
		}

		@header("Content-type: application/octet-stream");
		@header("Accept-Ranges: bytes");
		@header("Accept-Length:".filesize($path));

		$ua               = $_SERVER["HTTP_USER_AGENT"];
		$encoded_filename = rawurlencode($fileName);
		if (preg_match("/MSIE/", $ua))
		{
			@header('Content-Disposition: attachment; filename="'.$encoded_filename.'"');
		}
		else if (preg_match("/Firefox/", $ua))
		{
			@header("Content-Disposition: attachment; filename*=\"utf8''".$fileName.'"');
		}
		else
		{
			@header('Content-Disposition: attachment; filename="'.$fileName.'"');
		}

		if (substr_compare($path, '/mnt', strlen('/mnt')) == 0) //X-sendfile 断点下载
		{
			@header("X-Sendfile: ".$path);
		}
		else
		{
			$file   = fopen($path, 'r');
			$buffer = 1024;
			while (!feof($file))
			{
				$file_data = fread($file, $buffer);
				echo $file_data;
			}
			fclose($file);
		}

		return 0;
	}
}