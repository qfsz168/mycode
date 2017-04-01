<?php
namespace http;

/*
 * 高德/百度地图-API
 */
class AmapRestAPI
{
	/*
	 * 地理/逆地理编码, http://lbs.amap.com/api/webservice/reference/georegeo
	 *
	 * 采用济南分公司名义注册的key。日调用超量：400万次，1分钟调用超量：6万。
	 *
	 * Todo：后期需要考虑使用买家的key!!!
	 *
	 */
	public static $m_restapi = 'http://restapi.amap.com/v3/geocode/regeo?'.'output=json'.'&key=d327e8a6854d0bdfeda518f023fe9810';

	/*
	 * IP定位API, http://api.map.baidu.com/location/ip
	 *
	 * 每个key每天支持100万次调用，超过限制不返回数据。
	 *
	 * Todo：后期需要考虑使用买家的key!!!
	 */
	public static $m_apimap = 'http://api.map.baidu.com/location/ip?'.'ak=CiqSdD1GVtCTdflz5pfpd1eg'.'&coor=bd09ll';

	/*
	 * 根据经纬度坐标获取行政区号-&location=116.310003,39.991957
	 */
	public static function Get_Adcode($lantitude, $longitude, & $adcode)
	{
		$requestAPi = self::$m_restapi.'&location='.$longitude.','.$lantitude;
		$content    = file_get_contents($requestAPi);
		$jsonArr    = json_decode($content, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS);

		// 说明断网
		if (!isset($jsonArr) || is_null($jsonArr) || !isset($jsonArr['status']))
		{
			return -1;
		}

		// 说明获取失败
		if ($jsonArr['status'] != '1' && $jsonArr['info'] != 'OK')
		{
			return -1;
		}

		$adcode = $jsonArr['regeocode']['addressComponent']['adcode']; //北京市朝阳区110105
		return 0;
	}

	public static function Get_Location(& $lantitude, & $longitude)
	{
		$internetIp = \Browser::getInternetIp();

		$requestAPi = self::$m_apimap;
		if (isset($internetIp))
		{
			$requestAPi .= "&ip=$internetIp";
		}

		$content = file_get_contents($requestAPi);
		$jsonArr = json_decode($content, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS);

		// 说明断网
		if (!isset($jsonArr) || is_null($jsonArr) || !isset($jsonArr['status']))
		{
			return -1;
		}

		// 说明获取失败
		if ($jsonArr['status'] != '0')
		{
			return -1;
		}

		$lantitude = $jsonArr['content']['point']['y'];
		$longitude = $jsonArr['content']['point']['x'];

		return 0;
	}
}