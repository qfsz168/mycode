<?php
/**
 * Created by PhpStorm.
 * User: WangChongquan
 * Date: 2017/1/24 0024
 * Time: 15:32
 */

namespace app\api\model;

use http\ApacheAuthToken;
use think\Image;
use think\image\Exception;

class Img
{
	//缩略图清晰度(像素)
	const DEFN_O = 0; //原图

	//头像
	const DEFN_L_HP     = 30;
	const DEFN_M_HP     = 48;
	const DEFN_H_HP     = 96;
	const SET_DEFN_HP   = self::DEFN_O.",".self::DEFN_L_HP.",".self::DEFN_M_HP.",".self::DEFN_H_HP;
	const ARRAY_DEFN_HP = [
		self::DEFN_O,
		self::DEFN_L_HP,
		self::DEFN_M_HP,
		self::DEFN_H_HP,
	];

	//图片
	const DEFN_L     = 96;
	const DEFN_M     = 200;
	const DEFN_H     = 400;
	const SET_DEFN   = self::DEFN_O.",".self::DEFN_L.",".self::DEFN_M.",".self::DEFN_H;
	const ARRAY_DEFN = [
		self::DEFN_O,
		self::DEFN_L,
		self::DEFN_M,
		self::DEFN_H,
	];

	//图片返回类型
	const R_TYPE_PIC    = 1;//直接显示图片
	const R_TYPE_URL    = 2;//防盗链地址
	const R_TYPE_BASE64 = 3;//base64编码
	const SET_R_TYPE    = self::R_TYPE_PIC.",".self::R_TYPE_URL.",".self::R_TYPE_BASE64;
	const ARRAY_R_TYPE  = [
		self::R_TYPE_PIC,
		self::R_TYPE_URL,
		self::R_TYPE_BASE64,
	];

	//图片信息
	public $_width  = null;
	public $_height = null;
	public $_type   = null; //png|gif|bmp|...
	public $_mime   = null;

	/**
	 * Img constructor.获取图片基本信息
	 * @param string $file
	 */
	public function __construct($file)
	{
		$this->_file = $file;

		$im = new \Imagick($this->_file);

		$this->_type = $im->getImageFormat();

		$fInfo       = new \FInfo(FILEINFO_MIME_TYPE);
		$this->_mime = $fInfo->file($file);

		$imgSize       = $im->getImageGeometry();
		$this->_width  = $imgSize["width"];
		$this->_height = $imgSize["height"];

		$im->clear();
		$im->destroy();
	}

	/**
	 * 直接向浏览器输出图片
	 * @author 王崇全
	 * @param string $file 文件的完整路径
	 * @return void
	 */
	public static function RtnPic($file)
	{
		$info = getimagesize($file); //获取mime信息
		$fp   = fopen($file, "rb"); //二进制方式打开文件
		if (!$info || !$fp)
		{
			E(\EC::SOURCE_NOT_EXIST_ERROR, "文件不存在或不是图片");
		}

		ob_end_clean();
		header("Content-type: {$info['mime']}");
		fpassthru($fp); // 输出至浏览器
		exit;
	}

	/**
	 * 返回图片的防盗链地址
	 * @author 王崇全
	 * @param string $file 文件的完整路径
	 * @return string
	 */
	public static function RtnUrl($file)
	{
		return ApacheAuthToken::Get_AuthToken_URI($file);
	}

	/**
	 * 返回图片的base64编码
	 * @author 王崇全
	 * @param string $file     文件的完整路径
	 * @param bool   $compress 是否压缩尺寸
	 * @return string
	 */
	public static function RtnBase64($file, $compress = true)
	{
		if (!is_file($file))
		{
			E(\EC::SOURCE_NOT_EXIST_ERROR, "文件不存在或不是图片");
		}

		$fInfo    = new \FInfo(FILEINFO_MIME_TYPE);
		$fileMime = $fInfo->file($file);

		$bsae64 = 'data:'.$fileMime.';base64,'.base64_encode(file_get_contents($file));
		$size   = strlen($bsae64);

		//压缩数据，使其小于32K (为了兼容IE8以及提高性能)
		if (($size >= 1024 * 32 - 2) && $compress)//&& IE8)
		{
			//临时文件
			$tmpFile = DIR_TEMPS.uniqid();
			@copy($file, $tmpFile);

			$width = 750;
			while (true)
			{
				if ($size < 32 * 1024 - 2 || $width < 50)
				{
					break;
				}

				//压缩图片
				self::Resize($tmpFile, $width, $width);

				$fInfo    = new \FInfo(FILEINFO_MIME_TYPE);
				$fileMime = $fInfo->file($file);

				$bsae64 = 'data:'.$fileMime.';base64,'.base64_encode(file_get_contents($tmpFile));
				$size   = strlen($bsae64);

				$width -= 50;
			}

			//删除临时文件
			@unlink($tmpFile);
		}

		return $bsae64;
	}

	/**
	 * 创建缩略图 (详见下注释)
	 * 生成的缩略图会存放在 "原路径+thumb/" 下 缩略图精度即缩略图的文件名
	 * eg: ./img/picture 生成的缩略图可能是./img_thumb/200
	 * @author 王崇全
	 * @param string $file 文件的完整路径
	 * @param string $type pic,图片;ps,头像
	 * @return array 缩略图的路径
	 */
	public static function ThumbCreate($file, $type = "pic")
	{
		if (!is_file($file))
		{
			E(\EC::SOURCE_NOT_EXIST_ERROR, "不是有效的文件");
		}

		$pathInfo = pathinfo($file);
		$dirName  = $pathInfo["dirname"].DIRECTORY_SEPARATOR.$pathInfo["filename"]."_thumb".DIRECTORY_SEPARATOR;
		if (!is_dir($dirName))
		{
			if (!mk_dir($dirName))
			{
				E(\EC::DIR_MK_ERR, "存放图片缩略图的路径创建失败");
			}
		}

		$data = [];
		if ($type === "pic")
		{
			$data[self::DEFN_H] = self::MakeThumb($file, self::DEFN_H, self::DEFN_H, $dirName.self::DEFN_H);
			$data[self::DEFN_M] = self::MakeThumb($file, self::DEFN_M, self::DEFN_M, $dirName.self::DEFN_M);
			$data[self::DEFN_L] = self::MakeThumb($file, self::DEFN_L, self::DEFN_L, $dirName.self::DEFN_L);
		}
		elseif ($type === "ps")
		{
			$data[self::DEFN_H_HP] = self::MakeThumb($file, self::DEFN_H_HP, self::DEFN_H_HP, $dirName.self::DEFN_H_HP);
			$data[self::DEFN_M_HP] = self::MakeThumb($file, self::DEFN_M_HP, self::DEFN_M_HP, $dirName.self::DEFN_M_HP);
			$data[self::DEFN_L_HP] = self::MakeThumb($file, self::DEFN_L_HP, self::DEFN_L_HP, $dirName.self::DEFN_L_HP);
		}

		return $data;
	}

	/**
	 * 图像裁剪(不影响原图,详见下注释)
	 * 剪切后的图片会放在 "原路径+crop/$width."_".$height."_".$left."_".$top" 下
	 * eg: ./img/picture 的裁剪图片就是./img_crop/100_100_20_30
	 * @author 王崇全
	 * @param string $file   文件的完整路径
	 * @param int    $width  宽
	 * @param int    $height 高
	 * @param int    $left   起始位置右移(像素)
	 * @param int    $top    起始位置下移(像素)
	 * @return string 裁剪的图片的完整路径
	 */
	public static function ImgCrop($file, $width, $height, $left = 0, $top = 0)
	{
		$imagick = null;
		try
		{
			$imagick = new \Imagick ($file);
		}
		catch (Exception|\Exception $e)
		{
			E(\EC::API_ERR, "图片文件加载失败");
		}

		$imagick->cropImage($width, $height, $left, $top);


		$pathInfo = pathinfo($file);
		$dirName  = $pathInfo["dirname"].DIRECTORY_SEPARATOR.$pathInfo["filename"]."_crop".DIRECTORY_SEPARATOR;
		if (!is_dir($dirName))
		{
			if (!mk_dir($dirName))
			{
				E(\EC::DIR_MK_ERR, "存放裁剪后的图片的路径创建失败");
			}
		}

		$fileName = $width."_".$height."_".$left."_".$top;

		$imagick->writeImage($dirName.$fileName);

		$imagick->clear();
		$imagick->destroy();

		return $dirName.$fileName;
	}

	/**
	 * 调整图片尺寸
	 * @author 王崇全
	 * @date
	 * @param string      $file    原图的路径
	 * @param int         $Dw      调整时最大宽度;缩略图时的绝对宽度
	 * @param int         $Dh      调整时最大高度;缩略图时的绝对高度
	 * @param string|null $newFile 新图片的路径（如果为空，会覆盖原图）
	 * @return string 新图片的路径
	 */
	public static function Resize(string $file, int $Dw = 450, int $Dh = 450, string $newFile = null)
	{
		$imagick = null;
		try
		{
			$imagick = new \Imagick ($file);
		}
		catch (Exception|\Exception $e)
		{
			E(\EC::API_ERR, "图片文件加载失败");
		}

		$width  = $imagick->getImageWidth();
		$height = $imagick->getImageHeight();

		if ($width > $Dw)
		{
			$Par    = $Dw / $width;
			$width  = $Dw;
			$height = $height * $Par;
			if ($height > $Dh)
			{
				$Par    = $Dh / $height;
				$height = $Dh;
				$width  = $width * $Par;
			}
		}
		elseif ($height > $Dh)
		{
			$Par    = $Dh / $height;
			$height = $Dh;
			$width  = $width * $Par;
			if ($width > $Dw)
			{
				$Par    = $Dw / $width;
				$width  = $Dw;
				$height = $height * $Par;
			}
		}

		$imagick->resizeImage($width, $height, \Imagick :: FILTER_LANCZOS, 1);

		$newFile = $newFile??$file;
		$imagick->writeImage($newFile);

		$imagick->clear();
		$imagick->destroy();

		return $newFile;
	}

	/**
	 * 生成缩略图
	 * @author 王崇全
	 * @param string      $file    图片路径
	 * @param int         $width   缩略图的宽
	 * @param int         $height  缩略图的高
	 * @param string|null $newFile 缩略图的路径 （如果为空，会覆盖原图）
	 * @return string 缩略图的路径
	 */
	public static function MakeThumb(string $file, int $width, int $height, string $newFile = null)
	{
		$imagick = null;
		try
		{
			// load an image
			$imagick = new \Imagick ($file);
			$imagick->setFormat("png");
		}
		catch (Exception|\Exception $e)
		{
			E(\EC::API_ERR, "图片文件加载失败");
		}

		// get the current image dimensions
		$geo = $imagick->getImageGeometry();

/*		// 等比最大填充
		if (($geo['width'] / $width) < ($geo['height'] / $height))
		{
			$imagick->cropImage($geo['width'], floor($height * $geo['width'] / $width), 0, (($geo['height'] - ($height * $geo['width'] / $width)) / 2));
		}
		else
		{
			$imagick->cropImage(ceil($width * $geo['height'] / $height), $geo['height'], (($geo['width'] - ($width * $geo['height'] / $height)) / 2), 0);
		}*/

		// thumbnail the image
		$imagick->ThumbnailImage($width, $height, true);

		// save or show or whatever the image
		$thumbFIle = $newFile??$file;
		$imagick->writeImage($thumbFIle);
		$imagick->clear();
		$imagick->destroy();

		return $thumbFIle;
	}

}