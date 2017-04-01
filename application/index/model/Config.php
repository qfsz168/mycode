<?php
namespace app\api\model;

use app\api\controller\WebSite;
use think\Cache;

class Config
{
	const CONFIG_YML = CONF_PATH.'cfg.yml';  //配置文件
	const CONFIG_IMG = DIR_WEBSITE;  //logo icon路径

	const AUDIT_LEVEL_NO     = 1; //审核关闭
	const AUDIT_LEVEL_WEAK   = 2; //弱审核
	const AUDIT_LEVEL_STRICT = 3; //强审核

	//文件&课程审核开关(0,关;1,开)
	const SWITCH_AUDIT_ON  = 1;
	const SWITCH_AUDIT_OFF = 0;

	//直播审核开关(0,关;1,开)
	const SWITCH_AUDIT_LIVE_ON  = 1;
	const SWITCH_AUDIT_LIVE_OFF = 0;

	//直播服务器分发开关(0,关;1,开)
	const SWITCH_AUDIT_RELAY_ON  = 1;
	const SWITCH_AUDIT_RELAY_OFF = 0;

	//是否显示图标(0,不显示;1,显示)
	const WEBSITE_DISPLAY_ON  = 1;
	const WEBSITE_DISPLAY_OFF  = 0;

	//加载配置文件
	public static function LoadSelf()
	{
		if (!is_file(self::CONFIG_YML) || !is_readable(self::CONFIG_YML))
		{
			E(\EC::GET_CONFIG_ERROR, self::CONFIG_YML." 不存在或不可读");
		}

		return \Spyc::YAMLLoad(self::CONFIG_YML);
	}

	//写入配置文件
	public static function DumpSelf($dataArray, $mod = 0755)
	{
		$tmpFile = tempnam(dirname(self::CONFIG_YML), basename(self::CONFIG_YML));

		$content = \Spyc::YAMLDump($dataArray);

		if (false !== @file_put_contents($tmpFile, $content))
		{
			// rename does not work on Win32 before 5.2.6
			if (@rename($tmpFile, self::CONFIG_YML))
			{
				@chmod(self::CONFIG_YML, $mod & ~umask());

				return;
			}
		}

		// fail
		unlink($tmpFile);
		E(null, sprintf('Unable to write %s', self::CONFIG_YML));
	}

	/**
	 * 获取SMTP信息
	 *
	 * 邮箱： hancfin@qq.com
	 * 密码： hacfinqq
	 */
	public static function GetSMTP()
	{
		$dataArray = self::LoadSelf();
		if (isset($dataArray) && isset($dataArray['smtp']))
		{
			return $dataArray['smtp'];
		}

		return null;
	}

	/**
	 * 写入SMTP信息
	 */
	public static function SetSMTP($array)
	{
		$dataArray = self::LoadSelf();
		if (isset($dataArray) && isset($dataArray['smtp']))
		{
			$dataArray['smtp'] = $array;
		}

		self::DumpSelf($dataArray);

		//清除缓存
		Cache::rm(CACHE_WEBCFG);
	}

	/**
	 * 设置Switch信息
	 * @author 王崇全
	 * @param int $audit
	 * @param int $liveAudit
	 * @param int $relay
	 * @return void
	 * @throws \Exception
	 */
	public static function SetSwitch($audit = null, $liveAudit = null, $relay = null)
	{
		//清除缓存
		Cache::rm(CACHE_WEBCFG);

		if (is_null($audit) && is_null($liveAudit) && is_null($relay))
		{
			E(\EC::PARAM_ERROR, "参数不能都为空");
		}

		$webCfg = self::LoadSelf();

		if (isset($audit))
		{
			$webCfg["switch"]["audit"] = $audit;
		}
		if (isset($liveAudit))
		{
			$webCfg["switch"]["live_audit"] = $liveAudit;
		}
		if (isset($relay))
		{
			$webCfg["switch"]["relay"] = $relay;
		}

		self::DumpSelf($webCfg);
	}

	/**
	 * 获取Switch信息
	 * @author 王崇全
	 * @date
	 * @return null|array
	 */
	public static function GetSwitch()
	{
		$dataArray = self::LoadSelf();
		if (isset($dataArray) && isset($dataArray['switch']))
		{
			return $dataArray['switch'];
		}

		return null;
	}
}