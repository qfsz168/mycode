<?php

namespace data;

use app\api\model\File;
use function is_file;

class DataImage extends DataAbstract
{
	private $m_imageConvertCmd  = TOOL_IMAGEMAGICK.'convert';
	private $m_imageIdentifyCmd = TOOL_IMAGEMAGICK.'identify';
	private $m_quality          = 75;

	public function __construct($sorPath)
	{
		parent::__construct($sorPath);
		$this->m_bHandle = $this->check_handler();
	}

	/*
	 * 检查是否有安装imagemagick
	 */
	protected function check_handler()
	{
		$convert  = (strstr(shell_exec($this->m_imageConvertCmd.' -version'), 'Version: ImageMagick') != '') ? true : false;
		$identify = (strstr(shell_exec($this->m_imageIdentifyCmd.' -version'), 'Version: ImageMagick') != '') ? true : false;
		$rtn      = ($convert && $identify) ? true : false;

		return $rtn;
	}

	/*
	 * des.jpg JPEG 96x72 96x72+0+0 8-bit DirectClass 18.1KB 0.000u 0:00.000
	 */
	public function get_imagesize($imgPath)
	{
		if (!$this->m_bHandle)
		{
			return null;
		}


		$cmd = sprintf($this->m_imageIdentifyCmd." '%s'", $imgPath);
		$arr = explode(' ', shell_exec($cmd));
		if (!isset($arr))
		{
			return null;
		}

		$arr = @explode('x', @$arr[2]);

		return $arr;

	}

	/*
	 *  uuid_thumb_width_height.jpg
	 */
	public function create_thumbImage($desWidth, $desHeight, $fromCvt = false)
	{
		if (!$this->m_bHandle)
		{
			return null;
		}

		if ($fromCvt)
		{
			$str                  = "_cvt_jpgs_".DIRECTORY_SEPARATOR."page-0";
			$this->m_sorPathNoExt = str_replace($str, "", $this->m_sorPathNoExt);
		}
		$desPath = $this->m_sorPathNoExt.'_temp.jpg';


		// 命令行
		//[0] 表示使用第一副图像
		if ($desWidth > 0 && $desHeight > 0)
		{
			$cmd = sprintf($this->m_imageConvertCmd." '%s[0]' -resize '%sx%s>' -quality %s '%s' 2>&1", $this->m_sorPath, $desWidth, $desHeight, $this->m_quality, $desPath);
		}
		else if ($desWidth == 0) //固定高度
		{
			$cmd = sprintf($this->m_imageConvertCmd." '%s[0]' -resize 'x%s' -quality %s '%s' 2>&1", $this->m_sorPath, $desHeight, $this->m_quality, $desPath);
		}
		else if ($desHeight == 0)//固定宽度
		{
			$cmd = sprintf($this->m_imageConvertCmd." '%s[0]' -resize '%s' -quality %s '%s' 2>&1", $this->m_sorPath, $desWidth, $this->m_quality, $desPath);
		}
		//Todo:效率较低,待优化
		$rtn = shell_exec($cmd);

		//获取尺寸
		/*$arr = $this->get_imagesize($desPath);
		if (!isset($arr) || count(array_keys($arr)) != 2)
		{
			return 0;
		}*/

		$des = null;
			$des = $this->m_sorPathNoExt.File::CONVERT_THUMB.$desWidth;
			rename($desPath, $des);

		return $des;

	}
}