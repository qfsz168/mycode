<?php
namespace data;

use app\api\model\TaskConvert;

class DataPdf extends DataAbstract
{
	private $m_ffmpegCmd = TOOL_XPDF.'pdftopng';

	public function __construct($sorPath)
	{
		parent::__construct($sorPath);
		$this->m_bHandle = $this->check_handler();
	}

	/*
	 * 检查是否有安装xpdf
	 */
	protected function check_handler()
	{
		$rtn      = shell_exec($this->m_ffmpegCmd.' -version 2>&1');
		$pdftopng = (strstr($rtn, 'pdftopng version ') != '') ? true : false;
		$rtn      = ($pdftopng) ? true : false;

		return $rtn;
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


		// 如果m_sorPathNoExt 含有PRE_CONVERT_PDF，说明是Doc转换生成的pdf文件，此时m_sorPathNoExt需要修改
		$sorPathNoExt = $this->m_sorPathNoExt;
		if (substr_compare($this->m_sorPathNoExt, TaskConvert::CONVERT_PDF, -strlen(TaskConvert::CONVERT_PDF), strlen(TaskConvert::CONVERT_PDF)) == 0)
		{
			$sorPathNoExt = substr($sorPathNoExt, 0, strlen($sorPathNoExt) - strlen(TaskConvert::CONVERT_PDF));
		}

		//生成的文件会自动加上后缀 -000001.png
		$desPath = $sorPathNoExt.'_temp';

		// 命令行
		$cmd = sprintf($this->m_ffmpegCmd." -f 1 -l 1 '%s' '%s' 2>&1", $this->m_sorPath, $desPath);
		$rtn = shell_exec($cmd);

		// 将生成的文件转成jpg格式
		$desPath .= '-000001.png';
		$rePath = $sorPathNoExt.'.png';
		rename($desPath, $rePath);

		$img = new DataImage($rePath);
		$rel = $img->create_thumbImage($desWidth, $desHeight, $fromCvt = false);

		//删除中间png文件
		unlink($rePath);

		return $rel;

	}
}