<?php
namespace app\api\model;

use http\AmapRestAPI;

class FileMeta extends Base
{
	//type(直播类型)
	const TYPE_LIVE        = 1; //直播
	const TYPE_INTERACTION = 2; //互动
	const SET_TYPE         = self::TYPE_LIVE.",".self::TYPE_INTERACTION;
	const ARRAY_TYPE       = [
		self::TYPE_LIVE,
		self::TYPE_INTERACTION,
	];

	//自动时间
	protected $autoWriteTimestamp = "int";
	protected $updateTime         = false;

	private static $_keyArr = [
		'FileType',
		'MIMEType',
		'Make',
		'CameraModelName',
		'Orientation',
		'Software',
		'FocalLength',
		'ExposureTime',
		'FNumber',
		'ExposureProgram',
		'ShutterSpeedValue',
		'ApertureValue',
		'Aperture',
		'FieldOfView',
		'BrightnessValue',
		'ExposureCompensation',
		'MeteringMode',
		'LensInfo',
		'LensMake',
		'LensModel',
		'PlayDuration',
		'Duration',
		'MajorBrand',
		'AvgBitrate',
		'AvgBytesPerSec',
		'MaxBitrate',
		'EncodingSettings',
		'Encoder',
		'ImageWidth',
		'ImageHeight',
		'DisplayWidth',
		'DisplayHeight',
		'BitDepth',
		'BitsPerPixel',
		'BitsPerSample',
		'ColorType',
		'ColorComponents',
		'ColorSpace',
		'ColorSpaceData',
		'XResolution',
		'YResolution',
		'ResolutionUnit',
		'Compression',
		'CompressorID',
		'StreamNumber',
		'Interlace',
		'VideoCodecID',
		'VideoCodecName',
		'VideoCodec',
		'VideoFrameRate',
		'AudioFormat',
		'AudioCodecID',
		'AudioCodecName',
		'AudioCodec',
		'AudioChannels',
		'NumChanneate',
		'AudioBitsls',
		'AudioBitrPerSample',
		'BitsPerSample',
		'AudioSampleRate',
		'SampleRate',
		'DeviceManufacturer',
		'Artist',
		'Album',
		'Title',
		'Year',
		'Genre',
		'PageCount',
		'Pages',
		'Words',
	];

	/**
	 * Add元数据-经纬度-行政区划
	 * @author 王崇全
	 * @date
	 * @param string $fid
	 * @return false|int
	 */
	public function Add(string $fid)
	{
		// s1 获取元数据信息
		$metaArr = [];

		// s1.1 基本信息(文件信息)
		$f = new File();

		$fileInfo = $f->GetInfo($fid);
		if (!$fileInfo)
		{
			E(\EC::SOURCE_NOT_EXIST_ERROR);
		}

		$metaArr['FileName']       = $fileInfo['name'];
		$metaArr['FileSize']       = $fileInfo['filesize'];
		$metaArr['Type']           = File::GetTypeName($fileInfo['path']);
		$metaArr['FileModifyDate'] = $fileInfo['update_time'];
		$metaArr['FileAccessDate'] = $fileInfo['access_time'];
		$metaArr['FileCreateDate'] = $fileInfo['create_time'];
		$metaArr['UsrName']        = $fileInfo['nick_name'];

		// s1.2 exif信息
		$f        = new File;
		$fileInfo = $f->GetInfo($fid);
		$exiftool = \ExifToolBatch::getInstance(TOOL_EXIFTOOL . 'exiftool');
		$exiftool->add([
			'-*:*',
			$fileInfo['path'],
		]);
		$exifInfo = $exiftool->fetchDecodedOne();

		//过滤 exif信息
		if (isset($exifInfo))
		{
			foreach (self::$_keyArr as $key => $value)
			{
				if (isset($exifInfo[$value]))
				{
					$metaArr[$value] = $exifInfo[$value];
				}
			}
		}

		// s2 经纬度
		$lantitude = $longitude = null;
		//尝试读取文件自身的位置信息
		try
		{
			if (isset($exifInfo["GPSLatitude"]))
			{
				preg_match_all('/(-?\\d+)(\\.\\d+)?/i', $exifInfo["GPSLatitude"], $arr);
				$lantitude = @$arr[0][0] + @$arr[0][1] / 60 + @$arr[0][2] / 3600;
				if (strpos("S", $exifInfo["GPSLatitude"]) === true)
				{
					$lantitude *= -1;
				}
			}
			if (isset($exifInfo["GPSLongitude"]))
			{
				preg_match_all('/(-?\\d+)(\\.\\d+)?/i', $exifInfo["GPSLongitude"], $arr);
				$longitude = @$arr[0][0] + @$arr[0][1] / 60 + @$arr[0][2] / 3600;
				if (strpos("W", $exifInfo["GPSLatitude"]) === true)
				{
					$longitude *= -1;
				}
			}
		}
		catch (\Exception $e)
		{
		}
		//如果文件自身没有位置信息, 联网获取位置信息
		if (!$longitude || !$lantitude)
		{
			AmapRestAPI::Get_Location($lantitude, $longitude);
		}

		// s3 根据位置信息获取行政区码
		$adcode = null;
		if ($longitude && $lantitude)
		{
			AmapRestAPI::Get_Adcode($lantitude, $longitude, $adcode);
		}

		// s4 写入表中
		if (self::CheckExist($fid))
		{
			return self::save([
				"metainfo"  => json_encode($metaArr),
				"latitude"  => @$lantitude,
				"longitude" => @$longitude,
				"adcode"    => @$adcode,
			], [
				"fid" => $fid,
			]);
		}
		else
		{
			$new = self::create([
				"fid"       => $fid,
				"metainfo"  => json_encode($metaArr),
				"latitude"  => @$lantitude,
				"longitude" => @$longitude,
				"adcode"    => @$adcode,
			]);
			if (!$new)
			{
				E(\EC::SOURCE_ADD_ERR);
			}

			return 1;
		}
	}

	/**
	 * 删除元数据信息
	 * @author 王崇全
	 * @date
	 * @param string $fid
	 * @return int
	 */
	public function Del(string $fid): int
	{
		return self::destroy(["fid" => $fid,]);
	}

	/**
	 * 获取元数据信息
	 * @author 王崇全
	 * @date
	 * @param string $fid
	 * @return array
	 */
	public function GetInfo(string $fid)
	{
		$info = self::get(["fid" => $fid],DEF_CACHE_TIME_SQL_SELECT);
		if (!$info)
		{
			return [];
		}
		$info->setAttr("metainfo", json_decode($info->getAttr("metainfo"), true));

		return $info->hidden([
			"id",
			"fid",
			"create_time",
		])
			->toArray();
	}

	/**
	 * 获取地理坐标
	 * @param               $fid
	 * @return array  eg:["latitude"=>36.12345,"longitude"=>118.54321]
	 */
	public function GetPosition(string $fid)
	{
		$info = self::get(["fid" => $fid]);
		if (!$info)
		{
			return [];
		}

		return [
			"latitude"  => $info->getAttr("latitude"),
			"longitude" => $info->getAttr("longitude"),
		];
	}

	/**
	 * 设置地理坐标
	 * @param string $fid
	 * @param float  $lantitude
	 * @param float  $longitude
	 * @return void
	 */
	public function SetPosition(string $fid, float $lantitude, float $longitude): void
	{
		if (is_null($lantitude) || is_null($longitude))
		{
			E(\EC::PARAM_ERROR);
		}

		$data = [];
		if (isset($lantitude))
		{
			$data["latitude"] = $lantitude;
		}
		if (isset($longitude))
		{
			$data["longitude"] = $longitude;
		}

		if (!self::CheckExist($fid))
		{
			$data["fid"] = $fid;
			self::create($data);
		}
		else
		{
			self::save($data, ["fid" => $fid]);
		}
	}

	/**
	 * 检查是否存在记录
	 * @param string $fid
	 * @return bool
	 */
	public function CheckExist(string $fid)
	{
		$info = self::get(["fid" => $fid]);
		if (!$info)
		{
			return false;
		}

		return true;
	}
}