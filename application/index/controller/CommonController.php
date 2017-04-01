<?php

namespace app\index\controller;

use app\index\model\Upload;
use QRcode;
use think\captcha\Captcha;

class CommonController extends BaseController
{

	/**
	 * 获取验证码
	 * @author 王崇全
	 * @date
	 * @return \think\Response|array
	 */
	public function Captcha()
	{
		//数据接收
		$vali = $this->I([
			[
				"len",
				4,
				"d",
				"between:1,60",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}
		$len = self::$_input["len"];

		$fontSize = 25;

		$cap = new Captcha([
			// 验证码字符集合
			'codeSet'  => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
			// 验证码字体大小(px)
			'fontSize' => $fontSize,
			// 是否画混淆曲线
			'useCurve' => true,
			// 是否添加杂点
			'useNoise' => true,
			// 使用中文验证码
			'useZh'    => false,
			// 验证码图片高度
			'imageH'   => 50 * $fontSize / 25,
			// 验证码图片宽度
			'imageW'   => (50 + $len * 38) * $fontSize / 25,
			// 验证码位数
			'length'   => $len,
			// 验证成功后是否重置
			'reset'    => true,
		]);

		return $cap->entry();
	}

	/**
	 * 获取GUID
	 * @author 王崇全
	 * @return array
	 */
	public function UUID()
	{
		//数据接收&校验
		$vali = $this->I([
			[
				"count",
				1,
				"d",
				"require|number",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//生成GUID
		$r = [];
		for ($i = 0; $i < self::$_input["count"]; $i++)
		{
			$r[] = guid();
		}

		$count = count($r);
		$r     = $count == 1 ? $r[0] : $r;

		return $this->R(null, null, ["guid" => $r], $count);
	}

	/**
	 * 获取服务器当前时间
	 * @author 王崇全
	 * @return array
	 */
	public function Now()
	{
		return $this->R(null, null, ["time" => time_m()]);
	}

	/**
	 * 显示指定内容的二维码
	 * @author 王崇全
	 */
	public function QR()
	{
		//要改变header, 清除tp的header缓存
		ob_end_clean();

		//数据接收
		$vali = $this->I([
			[
				"text",
				null,
				"s",
				"require",
			],
		]);

		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		QRcode:: png(self::$_input["text"], false, QR_ECLEVEL_L, 5, 0); //生成二维码

		return $this->R();
	}

	/**
	 * 高级上传
	 * @author 王崇全
	 * @date
	 * @return array
	 */
	public function Upload()
	{
		crossdomain_cors(); //跨域上传!!!!

		//过滤OPTIONS请求
		if ($_SERVER['REQUEST_METHOD'] == "OPTIONS")
		{
			return $this->R();
		}

		//数据接收&校验
		$vali = $this->I([
			[
				"fid",
				null,
				"s",
				"require|alphaDash",
			],
			[
				"chunks",
				1,
				"d",
				"require|number|>:0",
			],
			[
				"chunk",
				0,
				"d",
				"require|number|>=:0",
			],
			[
				"md5",
				null,
				"s",
				"alphaDash",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		$info = Upload::UploadWUL(self::$_input["fid"], self::$_input["chunks"], self::$_input["chunk"], self::$_input["md5"]);

		return $this->R(null, null, $info);
	}

	/**
	 * 取消上传
	 * @author 王崇全
	 * @return array
	 */
	public function UploadCancel()
	{
		//**权限验证**
		$this->NeedToken();

		//**参数接收**
		$vali = $this->I([
			[
				"fid",
				null,
				"s",
				"require",
			],
		]);
		if ($vali !== true)
		{
			return $this->R(\EC::PARAM_ERROR, null, $vali);
		}

		//**功能实现**
		//Todo:清除碎片文件,临时文件

		//**数据返回**
		return $this->R(null, null);
	}
}