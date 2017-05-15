<?php
// +----------------------------------------------------------------------
// | 应用公共（函数）文件
// +----------------------------------------------------------------------

/**
 * 生成GUID
 * @author 王崇全
 * @date
 * @return string
 */
function guid()
{
	if (function_exists('com_create_guid'))
	{
		return com_create_guid();
	}
	else
	{
		mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid   = substr($charid, 0, 8).$hyphen.substr($charid, 8, 4).$hyphen.substr($charid, 12, 4).$hyphen.substr($charid, 16, 4).$hyphen.substr($charid, 20, 12);

		return strtolower($uuid);
	}
}

/**
 * 抛出异常处理
 *
 * @param integer $code      异常代码 默认为200
 * @param string  $msg       异常消息
 * @param string  $exception 异常类
 */
function E($code = 200, $msg = null, $exception = '')
{
	if (is_null($code))
	{
		$code = EC::API_ERR;
	}
	if (!$msg)
	{
		$msg = EC::GetMsg($code == 0 ? 200 : $code);
	}

	$e = $exception ?: '\think\Exception';
	throw new $e($msg, $code);
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string  $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type = 0)
{
	if ($type)
	{
		return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match)
		{
			return strtoupper($match[1]);
		}, $name));
	}
	else
	{
		return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
	}
}

//获取客户端的mac地址
function get_client_mac()
{
	@exec("arp -a", $array); // 执行arp -a命令，结果放到数组$array中
	foreach ($array as $value)
	{
		// 匹配结果放到数组$mac_array
		if (strpos($value, $_SERVER["REMOTE_ADDR"]) && preg_match("/(:?[0-9a-f]{2}[:-]){5}[0-9a-f]{2}/i", $value, $mac_array))
		{
			return $mac = $mac_array[0];
			break;
		}
	}

	return null;
}

// 不区分大小写的in_array实现
function in_array_case($value, $array)
{
	return in_array(strtolower($value), array_map('strtolower', $array));
}

/**
 * 遍历获取某路径下的文件,包括子文件夹
 * @author 王崇全
 * @param string $dir 目录名
 * @return array|null 包含完整文件路径级文件名的数组
 */
function get_files($dir)
{
	//如果本身就是个文件,直接返回
	if (is_file($dir))
	{
		return array($dir);
	}
	//创建数组,存储文件名
	$files = array();

	if (is_dir($dir) && ($dir_p = opendir($dir)))
	{//路径合法且能访问//创建目录句柄
		$ds = '/';  //目录分隔符
		while (($filename = readdir($dir_p)) !== false)
		{  //返回打开目录句柄中的一个条目
			if ($filename == '.' || $filename == '..')
			{
				continue;
			}  //排除干扰项
			$filetype = filetype($dir.$ds.$filename);  //获取本条目的类型(文件或文件夹)
			if ($filetype == 'dir')
			{  //如果收文件夹,
				$files = array_merge($files, get_files($dir.$ds.$filename));  //进行递归,并将结果合并到数组中
			}
			elseif ($filetype == 'file')
			{  //如果是文件,
				$files[] = mb_convert_encoding($dir.$ds.$filename, 'UTF-8', 'GBK');  //将文件名转成utf-8后存到数组
			}
		}
		closedir($dir_p);  //关闭目录句柄
	}
	else
	{//非法路径
		$files = null;
	}

	return $files;
}

/**
 * 获取毫秒精度的时间戳
 * @author 王崇全
 * @date
 * @return mixed
 */
function time_m()
{
	$mtime = explode(' ', microtime());

	return $mtime[1] + $mtime[0];
}

/**
 * 跨域资源共享 - 涉及安全性问题
 */
function crossdomain_cors()
{
	ob_end_clean();

	// 指定允许其他域名访问（所有）
	header('Access-Control-Allow-Origin:*');

	// 响应类型（所有：GET POST等）
	header('Access-Control-Allow-Methods:*');

	// 响应头设置（仅仅允许Content-Type）
	header('Access-Control-Allow-Headers:Content-Type');
	header('Access-Control-Allow-Credentials:true');
	header('Keep-Alive:timeout=5, max=100');
}

/**
 * 从文件中读取内容， 不能超过2M
 * @param  string $file   文件
 * @param int     $offset 起始位置 默认，开头
 * @param int     $length 长度 不能超过2M， 默认2M
 * @return bool|string
 */
function get_from_file($file, $offset = 0, $length = 0)
{
	if (!is_file($file))
	{
		return false;
	}

	//打开文件
	if (!$f = fopen($file, 'rb'))
	{
		return false;
	}

	//指针偏移
	if ($offset < 0)
	{
		fseek($f, $offset, SEEK_END);
	}
	else
	{
		fseek($f, $offset);
	}

	//长度

	if ($length > 1024 * 1024 * 2)
	{
		return false;
	}
	elseif ($length == 0)
	{
		$length = 1024 * 1024 * 2;
	}

	return @fread($f, $length);
}

/**
 * 删除文件夹及其内部文件
 * @param $dir
 * @return bool
 */
function dir_del($dir)
{
	if (!is_dir($dir))
	{
		return false;
	}

	//先删除目录下的文件：
	$dh = opendir($dir);
	while ($file = readdir($dh))
	{
		if ($file != "." && $file != "..")
		{
			$fullpath = $dir."/".$file;
			if (!is_dir($fullpath))
			{
				@unlink($fullpath);
			}
			else
			{
				dir_del($fullpath);
			}
		}
	}
	closedir($dh);

	//删除当前文件夹：
	if (!@rmdir($dir))
	{
		return false;
	}

	return true;
}

/**
 * 强制浏览器进行缓存
 * @param int  $expires
 * @param bool $now
 */
function cache_olgt($expires = 20, $now = false)
{
	$now = $now ?? time();
	header("Cache-Control: public");
	header("Pragma: cache");
	header("Last-Modified: ".date('r', $now));
	header("Expires: ".date("r", ($now + $expires)));
	header("Cache-Control: max-age=$expires");

	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $expires > time()))
	{
		header("HTTP/1.1 304 Not Modified");
		exit();
	}
}

/**
 * Etag缓存
 * @param int    $expires
 * @param string $etag
 * @param bool   $time
 */
function cache_etag($expires = 20, $etag = 'xxx', $time = false)
{
	$time = $time ? $time : time();
	header("Last-Modified: ".date('r', $time));
	header("Expires: ".date("r", ($time + $expires)));
	header("Cache-Control: max-age=$expires");
	header("Etag: $etag");

	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $expires > time()))
	{
		header("Etag: $etag", true, 304);
		exit();
	}

}

/**
 * 返回数组的维度
 * @param  [type] $arr [description]
 * @return [type]      [description]
 */
function array_level($arr)
{
	$al = array(0);
	function aL($arr, &$al, $level = 0)
	{
		if (is_array($arr))
		{
			$level++;
			$al[] = $level;
			foreach ($arr as $v)
			{
				aL($v, $al, $level);
			}
		}
	}

	aL($arr, $al);

	return max($al);
}

/**
 * 按照值删除数组元素（保持原索引）
 * @param      $array
 * @param      $val
 * @param bool $strict
 * @return bool
 */
function array_del_by_val(&$array, $val, $strict = false)
{
	if (!isset($val) || !is_array($array))
	{
		return false;
	}

	$keys = array_keys($array, $val, $strict);
	foreach ($keys as $key)
	{
		unset($array[$key]);
	}

	return true;
}

/**
 * 将$table2的内容赋给$table1
 * @param array  $table1
 *                     eg: array(
 *                     ['uid'=>1,name=>'tom'],
 *                     ['uid'=>2,name=>'lily'],
 *                     )
 *
 * @param string $key1 eg:'uid'
 * @param array  $table2
 *                     eg: array(
 *                     ['userid'=>1,age=>12],
 *                     ['userid'=>2,age=>13],
 *                     )
 * @param string $key2 eg:'userid'
 * @return array|bool
 *                     eg: array(
 *                     ['uid'=>1,name=>'tom',age=>12],
 *                     ['uid'=>2,name=>'lily',age=>13],
 *                     )
 */
function array_merge2d($table1, $key1, $table2, $key2)
{
	//获取$table2的所有列
	$colNames2 = [];
	foreach ($table2 as $row2)
	{
		$colNames2 = array_keys($row2);
		break;
	}

	//去除配对的列
	$k = array_search($key2, $colNames2);
	if ($k === false)
	{
		return false;
	}
	unset($colNames2[$k]);

	//用表2填充表1
	foreach ($table1 as $rowNum1 => &$row1)
	{
		foreach ($table2 as $rowNum2 => &$row2)
		{//逐行在表2中匹配

			if ($row1[$key1] == $row2[$key2])
			{ //匹配成功

				//逐列将表2 $colName2行的内容赋给表1的$rowNum1行
				foreach ($colNames2 as $colName2)
				{
					$row1[$colName2] = $row2[$colName2];
				}

				//删掉表2的$colName2行,提高效率
				unset($table2[$rowNum2]);
				break;
			}

		}

	}

	return $table1;
}

/**
 * 递归地创建目录
 * @author 王崇全
 * @param string $pathname 路径
 * @param int    $mode     数字 1 表示使文件可执行，数字 2 表示使文件可写，数字 4 表示使文件可读。相加即$mode
 * @return bool
 */
function mk_dir($pathname, $mode = 0777)
{
	if (is_dir($pathname))
	{
		return true;
	}

	return mkdir($pathname, $mode, true);
}

/**
 * 生成上传文件的的Sub路径 年-月-日-时
 */
function dir_sub_date($sUploadRootDir = null)
{
	$arrays = getdate();
	$year   = $arrays['year'];
	$mon    = $arrays['mon'];
	$day    = $arrays['mday'];
	$hours  = $arrays['hours'];

	$dir = empty($sUploadRootDir) ? $year : $sUploadRootDir.$year;
	if (!is_dir($dir))
	{
		mkdir($dir, 0777, true);
		chmod($dir, 0777);
	}

	$dir .= '/'.$mon;
	if (!is_dir($dir))
	{
		mkdir($dir, 0777, true);
		chmod($dir, 0777);
	}

	$dir .= '/'.$day;
	if (!is_dir($dir))
	{
		mkdir($dir, 0777, true);
		chmod($dir, 0777);
	}

	$dir .= '/'.$hours;
	if (!is_dir($dir))
	{
		mkdir($dir, 0777, true);
		chmod($dir, 0777);
	}

	return $dir.DIRECTORY_SEPARATOR;
}

/**
 * 将编号列表转为数组
 * @author 王崇全
 * @param string $ids 编号列表
 * @param string $sep 分隔符
 * @return array
 */
function ids2array(string $ids = null, string $sep = "|")
{
	$arr = explode($sep, trim($ids, $sep));
	if (empty($arr) || reset($arr) === "")
	{
		return [];
	}

	return $arr;
}

/**
 * 获取客户端的浏览器信息
 * @author 王崇全
 * @date
 * @param string $ua
 * @return array [名称,版本]
 */
function get_browse(string $ua)
{
	if (stripos($ua, "Firefox/") > 0)
	{
		preg_match("/Firefox\/([^;)]+)+/i", $ua, $b);
		$exp[0] = "Firefox";
		$exp[1] = $b[1]; //获取火狐浏览器的版本号
	}
	elseif (stripos($ua, "Maxthon") > 0)
	{
		preg_match("/Maxthon\/([\d\.]+)/", $ua, $aoyou);
		$exp[0] = "傲游";
		$exp[1] = $aoyou[1];
	}
	elseif (stripos($ua, "MSIE") > 0)
	{
		preg_match("/MSIE\s+([^;)]+)+/i", $ua, $ie);
		$exp[0] = "IE";
		$exp[1] = $ie[1]; //获取IE的版本号
	}
	elseif (stripos($ua, "OPR") > 0)
	{
		preg_match("/OPR\/([\d\.]+)/", $ua, $opera);
		$exp[0] = "Opera";
		$exp[1] = $opera[1];
	}
	elseif (stripos($ua, "Edge") > 0)
	{
		//win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
		preg_match("/Edge\/([\d\.]+)/", $ua, $Edge);
		$exp[0] = "Edge";
		$exp[1] = $Edge[1];
	}
	elseif (stripos($ua, "Chrome") > 0)
	{
		preg_match("/Chrome\/([\d\.]+)/", $ua, $google);
		$exp[0] = "Chrome";
		$exp[1] = $google[1]; //获取google chrome的版本号
	}
	elseif (stripos($ua, 'rv:') > 0 && stripos($ua, 'Gecko') > 0)
	{
		preg_match("/rv:([\d\.]+)/", $ua, $IE);
		$exp[0] = "IE";
		$exp[1] = $IE[1];
	}
	else
	{
		$exp[0] = "";
		$exp[1] = "";
	}

	return [
		$exp[0],
		$exp[1],
	];
}

/**
 * 获取操作系统类型
 * @author 王崇全
 * @date
 * @param string $agent
 * @return string
 */
function get_plat(string $agent)
{
	if (false !== stripos($agent, 'win') && stripos($agent, '95'))
	{
		$os = 'Windows 95';
	}
	else if (false !== stripos($agent, 'win 9x') && stripos($agent, '4.90'))
	{
		$os = 'Windows ME';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, '98'))
	{
		$os = 'Windows 98';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 5.0'))
	{
		$os = 'Windows 2000';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 5.1'))
	{
		$os = 'Windows XP';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 5.2'))
	{
		$os = 'Windows XP';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 6.0'))
	{
		$os = 'Windows Vista';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 6.1'))
	{
		$os = 'Windows 7';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 6.2'))
	{
		$os = 'Windows 8';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 6.4'))
	{
		$os = 'Windows 10';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt 10'))
	{
		$os = 'Windows 10';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, 'nt'))
	{
		$os = 'Windows NT';
	}
	else if (false !== stripos($agent, 'win') && false !== stripos($agent, '32'))
	{
		$os = 'Windows 32';
	}
	else if (false !== stripos($agent, 'linux'))
	{
		$os = 'Linux';
	}
	else if (false !== stripos($agent, 'unix'))
	{
		$os = 'Unix';
	}
	else if (false !== stripos($agent, 'sun') && false !== stripos($agent, 'os'))
	{
		$os = 'SunOS';
	}
	else if (false !== stripos($agent, 'ibm') && false !== stripos($agent, 'os'))
	{
		$os = 'IBM OS/2';
	}
	else if (false !== stripos($agent, 'Mac') && false !== stripos($agent, 'PC'))
	{
		$os = 'Macintosh';
	}
	else if (false !== stripos($agent, 'PowerPC'))
	{
		$os = 'PowerPC';
	}
	else if (false !== stripos($agent, 'AIX'))
	{
		$os = 'AIX';
	}
	else if (false !== stripos($agent, 'HPUX'))
	{
		$os = 'HPUX';
	}
	else if (false !== stripos($agent, 'NetBSD'))
	{
		$os = 'NetBSD';
	}
	else if (false !== stripos($agent, 'BSD'))
	{
		$os = 'BSD';
	}
	else if (false !== stripos($agent, 'OSF1'))
	{
		$os = 'OSF1';
	}
	else if (false !== stripos($agent, 'IRIX'))
	{
		$os = 'IRIX';
	}
	else if (false !== stripos($agent, 'FreeBSD'))
	{
		$os = 'FreeBSD';
	}
	else if (false !== stripos($agent, 'teleport'))
	{
		$os = 'teleport';
	}
	else if (false !== stripos($agent, 'flashget'))
	{
		$os = 'flashget';
	}
	else if (false !== stripos($agent, 'webzip'))
	{
		$os = 'webzip';
	}
	else if (false !== stripos($agent, 'offline'))
	{
		$os = 'offline';
	}
	else
	{
		$os = '';
	}

	return $os;
}

/**
 * 构造 区间查询条件
 * @author 王崇全
 * @date
 * @param array       $map       查询条件数组
 * @param string      $filedName 字段名
 * @param string|int  $min       最小值
 * @param  string|int $max       最大值
 * @return void
 */
function sql_map_region(array &$map, string $filedName, $min, $max)
{
	if (isset($min) && !isset($max))
	{
		$map["$filedName"] = [
			">=",
			$min,
		];
	}
	else if (!isset($min) && isset($max))
	{
		$map["$filedName"] = [
			"<=",
			$max,
		];
	}
	else if (isset($min) && isset($max))
	{
		$map["$filedName"] = [
			"between",
			[
				$min,
				$max,
			],
		];
	}
}

/**
 * 删除数组中的某个键（支持多维数组）
 * @author 王崇全
 * @param array  $array  数组
 * @param string $delKey 键
 * @return void
 */
function array_del_key(array &$array, string $delKey)
{
	foreach ($array as $key => &$value)
	{
		if ($key === $delKey)
		{
			unset($array[$key]);
		}
		if (is_array($value))
		{
			array_del_key($value, $delKey);
		}
	}
}

/**
 * 生成用户身份识别码
 * @author 王崇全
 * @date
 * @return string
 */
function make_uic(): string
{
	$openid = guid();
	if (IE8)
	{
		$request = \think\Request::instance();
		$ip      = $request->ip();
		$ua      = $request->header("user-agent");

		return md5($ip.$ua);
	}

	return $openid;
}

/**
 * 获取用户身份识别码
 * @author 王崇全
 * @date
 * @return string
 */
function get_uic(): string
{
	$openid = \think\Cookie::get("uic");
	if (IE8)
	{
		$request = \think\Request::instance();
		$ip      = $request->ip();
		$ua      = $request->header("user-agent");

		return md5($ip.$ua);
	}

	return $openid??guid();
}

/**
 * 递归地合并两个数组
 * @author 王崇全
 * 如果输入的数组中有相同的字符串键名，则这些值会被合并到一个数组中去，这将递归下去，
 * 因此如果一个值本身是一个数组，本函数将按照相应的条目把它合并为另一个数组。
 * 如果数组具有相同的数组键名：此键名对应的键值完全相同的话就覆盖；不完全相同就附加。
 * @param array $arr1
 * @param array $arr2
 * @return array
 */
function arrays_merge_recursive(array $arr1, array $arr2)
{
	foreach ($arr2 as $key => $value)
	{
		if (is_array($value) && array_key_exists($key, $arr1))
		{
			$arr1[$key] = arrays_merge_recursive($arr1[$key], $arr2[$key]);
		}
		else
		{
			$arr1[$key] = $value;
		}

	}

	return $arr1;
}

/**
 * 构造数组
 * 以某个一维数组的值为键名，纵深的创建数据
 * @author 王崇全
 * @date   2017.04.25 16:02
 * @param array $arr
 * @param mixed $value
 * @return array
 */
function array_create_by_values(array $arr, $value)
{
	$tmpArr = $value;
	while (!is_null($key = array_pop($arr)))
	{
		$tmpArr = [
			$key => $tmpArr,
		];
	}

	return $tmpArr;
}

/**
 * 小文件下载
 * @author 王崇全
 * @date
 * @param string      $file
 * @param string|null $downloadFileName
 * @return void
 */
function download_file(string $file, string $downloadFileName = null)
{
	if (is_file($file))
	{
		if (!isset($downloadFileName))
		{
			$pathInfo         = pathinfo($file);
			$ext              = isset($pathInfo["extension"]) ? ".".$pathInfo["extension"] : "";
			$downloadFileName = $pathInfo["basename"].$ext;
		}

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$downloadFileName);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: '.filesize($file));
		header("Accept-Length:".filesize($file));

		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua))
		{
			$encoded_filename = rawurlencode($downloadFileName);
			@header('Content-Disposition: attachment; filename="'.$encoded_filename.'"');
		}
		else if (preg_match("/Firefox/", $ua))
		{
			@header("Content-Disposition: attachment; filename*=\"utf8''".$downloadFileName.'"');
		}
		else
		{
			@header('Content-Disposition: attachment; filename="'.$downloadFileName.'"');
		}

		ob_clean();
		flush();
		readfile($file);
		exit;
	}
}

/**
 * 数组转树-非递归
 * @author 王崇全
 * @date
 * @param array  $tree
 * @param string $pid
 * @return array
 */
function get_tree(array $tree, string $pid)
{
	//目标数组
	$rest = [];

	//索引数组，用于记录节点在目标数组的位置
	$index = [];

	foreach ($tree as $node)
	{
		//给每个节点附加一个child项
		$node["child"] = [];
		unset($node["leftcode"]);
		unset($node["rightcode"]);

		if ($node["parentid"] == $pid)
		{
			$i = count($rest);

			$rest[$i] = $node;

			$index[$node["groupid"]] =& $rest[$i];
		}
		else
		{
			$i = count($index[$node["parentid"]]["child"]);

			$index[$node["parentid"]]["child"][$i] = $node;

			$index[$node["groupid"]] =& $index[$node["parentid"]]["child"][$i];
		}
	}

	return $rest;
}

/**
 * 数组转树-递归
 * @author 王崇全
 * @date
 * @param $array
 * @param $pid
 * @return array
 */
function get_tree_recursion($array, $pid)
{
	$tree = [];
	foreach ($array as $v)
	{
		if ($v['parentid'] == $pid)
		{
			//父亲找到儿子
			$v['child'] = get_tree_recursion($array, $v['groupid']);
			$tree[]     = $v;
		}
	}

	return $tree;
}

/**
 * 解析参数列表
 * @author 王崇全
 * @date
 * @param string $query 参数列表
 * @return array 参数列表
 */
function query_to_array(string $query)
{
	$params = [];
	$query  = explode("&", trim($query));
	foreach ($query as $v)
	{
		$v = explode("=", trim($v));

		$params[trim($v[0])] = trim($v[1]);
	}

	return $params;
}

/**
 * 格式化数字
 * @author 王崇全
 * @date
 * @param int|float|double $number   数字
 * @param int              $decimals 小数位数
 * @return null|string
 */
function number_fmt($number, int $decimals = 4)
{
	return number_format((float)$number, $decimals, ".", "")??null;
}