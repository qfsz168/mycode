<?php
namespace http;

class ApacheAuthToken
{
	/*
	 * LoadModule auth_token_module  modules/mod_auth_token.so
	 * Alias /preview      "/mnt"
	 * <Location /preview/>
	 * AuthTokenSecret     "s3cr3tstr1ng"
	 * AuthTokenPrefix     /preview/
	 * AuthTokenTimeout    3600
	 * AuthTokenLimitByIp  on
	 * </Location>
	 */
	public static function Get_AuthToken_URI($sRelPath)
	{
		$secret        = "s3cr3tstr1ng";     // Same as AuthTokenSecret
		$protectedPath = "/preview/";        // Same as AuthTokenPrefix
		$ipLimitation  = true;               // Same as AuthTokenLimitByIp
		$hexTime       = dechex(time());     // Time in Hexadecimal

		// �����·��Ϊȫ·�� /mnt/volume1/2015/12/2/18/3/24637b61-a010-49cc-8c2d-6a0005abf2e5
		// ��Ҫ��/volume1/2015/12/2/18/3/24637b61-a010-49cc-8c2d-6a0005abf2e5 ·��
		$fileName = substr($sRelPath, 4); // The file to access

		// Let's generate the token depending if we set AuthTokenLimitByIp
		if ($ipLimitation)
		{
			$token = md5($secret.$fileName.$hexTime.$_SERVER['REMOTE_ADDR']);
		}
		else
		{
			$token = md5($secret.$fileName.$hexTime);
		}

		// We build the url
		$httpOrigin = null;
		if (isset($_SERVER['HTTP_HOST']))
		{
			$httpOrigin = 'http://'.$_SERVER['HTTP_HOST'];
		}
		else
		{
			$httpOrigin = 'http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'];
		}

		$url = $httpOrigin.$protectedPath.$token."/".$hexTime.$fileName;

		return $url;
	}
}