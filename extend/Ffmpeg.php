<?php

/**
 * Created by PhpStorm.
 * User: hacfin
 * Date: 2017/5/25
 * Time: 15:36
 */
define('FFMPEG_PATH', '/usr/local/ffmpeg2/bin/ffmpeg -i "%s" 2>&1');

class Ffmpeg
{

	function getVideoInfo($file)
	{
		$command = sprintf(FFMPEG_PATH, $file);

		ob_start();
		passthru($command);
		$info = ob_get_contents();
		ob_end_clean();

		$data = array();
		if (preg_match("/Duration: (.*?), start: (.*?), bitrate: (\d*) kb\/s/", $info, $match))
		{
			$data['duration'] = $match[1]; //播放时间
			$arr_duration     = explode(':', $match[1]);
			$data['seconds']  = $arr_duration[0] * 3600 + $arr_duration[1] * 60 + $arr_duration[2]; //转换播放时间为秒数
			$data['start']    = $match[2]; //开始时间
			$data['bitrate']  = $match[3]; //码率(kb)
		}
		if (preg_match("/Video: (.*?), (.*?), (.*?)[,\s]/", $info, $match))
		{
			$data['vcodec']     = $match[1]; //视频编码格式
			$data['vformat']    = $match[2]; //视频格式
			$data['resolution'] = $match[3]; //视频分辨率
			$arr_resolution     = explode('x', $match[3]);
			$data['width']      = $arr_resolution[0];
			$data['height']     = $arr_resolution[1];
		}
		if (preg_match("/Audio: (\w*), (\d*) Hz/", $info, $match))
		{
			$data['acodec']      = $match[1]; //音频编码
			$data['asamplerate'] = $match[2]; //音频采样频率
		}
		if (isset($data['seconds']) && isset($data['start']))
		{
			$data['play_time'] = $data['seconds'] + $data['start']; //实际播放时间
		}
		$data['size'] = filesize($file); //文件大小

		return $data;
	}

}