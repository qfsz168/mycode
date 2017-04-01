<?php
namespace data;

class DataVideo extends DataAbstract
{
	private $m_ffmpegCmd = TOOL_FFMPEG.'ffmpeg';

	public function __construct($sorPath, $fileid = null)
	{
		parent::__construct($sorPath);

		$this->m_bHandle = $this->check_handler();
	}

	/*
	 * 检查是否有安装ffmepg
	 */
	protected function check_handler()
	{
		$rtn    = shell_exec($this->m_ffmpegCmd.' -version');
		$ffmpeg = (strstr($rtn, 'ffmpeg version ') != '') ? true : false;
		$rtn    = ($ffmpeg) ? true : false;

		return $rtn;
	}

	/*
	 * uuid_thumb_width_height.jpg
	 * ffmpeg -i 26_MPEG_720x576p25.mpg -y -f image2 -ss 3 -t 00:00:00:01  -vframes 1 -s 320x240 test.jpg
	 * ffmpeg -i test.asf -vframes 30 -y -f gif a.gif
	 */
	public function create_thumbImage($desWidth, $desHeight)
	{
		if (!$this->m_bHandle)
		{
			return null;
		}

		$desPath = $this->m_sorPathNoExt.'_temp.jpg';

		// 命令行
		// -ss 3 -t 00:00:00:01
		// -ss 3 -t 0.001
		//生成原始尺寸的图像
		$cmd = sprintf($this->m_ffmpegCmd." -i '%s' -y -ss 3 -t 0.001 -f image2 '%s'", $this->m_sorPath, $desPath);
		$rtn = shell_exec($cmd);

		//视频时长有可能不到3秒
		if (!file_exists($desPath))
		{
			$cmd = sprintf($this->m_ffmpegCmd." -i '%s' -y -f image2 '%s'", $this->m_sorPath, $desPath);
			$rtn = shell_exec($cmd);
		}

		if (file_exists($desPath))
		{
			$reNamePath = $this->m_sorPathNoExt.'.jpg';
			rename($desPath, $reNamePath);

			// 转成要求尺寸
			$img = new DataImage($reNamePath);
			$rel = $img->create_thumbImage($desWidth, $desHeight, $fromCvt = false);
		}

		return null;
	}
}