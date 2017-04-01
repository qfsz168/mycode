<?php

/**
 * Created by PhpStorm.
 * User: hacfin
 * Date: 2017/3/29
 * Time: 17:48
 */
class StringTool
{
	/**
	 * @var string
	 */
	protected $_str = ""; //要解析的标签
	/**
	 * @var string
	 */
	protected $_tag_left = "<{>"; //左标签
	/**
	 * @var string
	 */
	protected $_tag_right = "<}>"; //右标签

	/**
	 * StringTool constructor.
	 * @param string|null $str
	 */
	function __construct(string $str = null)
	{
		$this->_str = $str??"";
	}


	/**
	 * 生成标签
	 * @author 王崇全
	 * @date
	 * @param string     $tagName 标签名
	 * @param array|null $attr    属性列表（["属性名"=>"属性值"，...]）
	 * @param bool       $dqm     是否用双引号
	 * @return string
	 */
	public function CreateTag(string $tagName, array $attr = null, bool $dqm = false): string
	{
		$qm  = $dqm ? '"' : "'";
		$str = "{$this->_tag_left} name=$qm{$tagName}$qm";

		if (is_array($attr))
		{
			foreach ($attr as $attrName => $attrValue)
			{
				$attrName  = trim($attrName);
				$attrValue = trim($attrValue);

				if (!isset($attrName) || !isset($attrValue) || !is_string($attrName) || !is_string((string)$attrValue) || $attrName == "name")
				{
					continue;
				}
				$str .= " {$attrName}=$qm{$attrValue}$qm";
			}
		}

		$str .= " {$this->_tag_right}";

		return $str;
	}

	/**
	 * 获取指定名称的标签
	 * @author 王崇全
	 * @date
	 * @param string    $tagName 标签名
	 * @param bool|null $withTag 结果是否包含标签本身
	 * @param bool|null $all     是否获取所有同名（$tagName）标签
	 * @return array
	 */
	public function GetTagByName(string $tagName, bool $withTag = true, bool $all = false)
	{
		$tags = $this->GetTagAll($withTag);

		//找到名为 $tagName 的标签

		$lt  = $withTag ? $this->_tag_left : "";
		$rt  = $withTag ? $this->_tag_right : "";
		$reg = "/$lt.*name=['\"]{$tagName}['\"].*$rt/isU";

		$tgtTags = [];
		foreach ($tags as $tag)
		{
			if (preg_match($reg, $tag))
			{
				$tgtTags[] = $tag;

				if (!$all)
				{
					break;
				}
			}
		}

		return $all ? $tgtTags : (reset($tgtTags) ?: null);
	}

	/**
	 * 获取标签的属性值
	 * @author 王崇全
	 * @date
	 * @param string    $tagName 标签名称
	 * @param string    $tagAttr 标签属性
	 * @param bool|null $all     是否获取所有同名（$tagName）标签的$tagAttr属性值
	 * @return array|string|null
	 */
	public function GetTagAttr(string $tagName, string $tagAttr, bool $all = false)
	{
		$tgtTags = $this->GetTagByName($tagName, true, $all);
		if (!$tgtTags)
		{
			return null;
		}
		if (!is_array($tgtTags))
		{
			$tgtTags = [$tgtTags];
		}

		$values = [];
		foreach ($tgtTags as $tgtTag)
		{
			$reg = "/$this->_tag_left.*name=['\"]{$tagName}['\"].*?$tagAttr=['\"](\S*?)['\"].*$this->_tag_right/isU";
			if (preg_match_all($reg, $tgtTag, $matches))
			{
				$values[] = $matches[1][0];
				if (!$all)
				{
					break;
				}
				continue;
			}

			$reg = "/$this->_tag_left.*?$tagAttr=['\"](\S*?)['\"].*name=['\"]{$tagName}['\"].*$this->_tag_right/isU";
			if (preg_match_all($reg, $tgtTag, $matches))
			{
				$values[] = $matches[1][0];
				if (!$all)
				{
					break;
				}
				continue;
			}

			$values[] = null;
			if (!$all)
			{
				break;
			}
		}

		return $all ? $values : (reset($values) ?: null);
	}

	/**
	 * 替换标签-批量
	 * @author 王崇全
	 * @date
	 * @param array $comtents 要将标签替换成的内容（数目少于标签数，后面的标签将不替换；数目多于标签数，后面的内容会被忽略；中间有不需要替换的，用null代替）
	 * @param bool  $isStrict 是否严格匹配标签和$comtents的数目（如果开启，数目不一致或有无效内容，会抛出异常）
	 * @throws Exception
	 * @return string 替换后的字符串
	 */
	public function ReplaceTags(array $comtents, bool $isStrict = true)
	{
		$str       = $this->_str;
		$tags      = $this->GetTagAll(true);
		$tagsCount = count($tags);
		if ($isStrict && $tagsCount != count($comtents))
		{
			throw new \Exception("内容数与标签数不一致");
		}

		try
		{
			for ($i = 0; $i < $tagsCount; $i++)
			{
				if (!isset($comtents[$i]) || !is_string($comtents[$i]))
				{
					if ($isStrict)
					{
						throw new \Exception("内容不是字符串");
					}
					else
					{
						continue;
					}
				}

				$str = $this->mb_str_replace($tags[$i], (string)$comtents[$i], $str);
			}
		}
		catch (\Exception $e)
		{
			$msg = $e->getMessage();
			throw new \Exception("替换失败（$msg）");
		}

		return $str;
	}

	/**
	 * 替换标签-根据标签名
	 * @author 王崇全
	 * @date
	 * @param string $comtent 要将标签替换成的内容
	 * @param bool   $all     如果有多个名为 $tagName 的标签，是否都替换（如果标签完全相同，$all 为 false 也会都替换）
	 * @return string 替换后的字符串
	 */
	public function ReplaceTagsByName(string $tagName, string $comtent, bool $all = false)
	{
		$tags = $this->GetTagAll();
		$str  = $this->_str;

		//找到名为 $tagName 的标签
		$reg    = "/$this->_tag_left.*name=['\"]{$tagName}['\"].*$this->_tag_right/isU";
		$indexs = [];
		$i      = 0;
		foreach ($tags as $tag)
		{
			if (preg_match($reg, $tag))
			{
				$indexs[] = $i;
				if (!$all)
				{
					break;
				}
			}

			$i++;
		}

		//替换
		foreach ($indexs as $i)
		{
			$str = $this->mb_str_replace($tags[$i], $comtent, $str);
			if (!$all)
			{
				break;
			}
		}

		return $str;
	}

	/**
	 * 获取字符串中的所有标签
	 * @author 王崇全
	 * @date
	 * @param bool $withTag 结果是否包含标签本身
	 * @return array 解析结果
	 */
	public function GetTagAll(bool $withTag = true)
	{
		$reg = "#$this->_tag_left(.+?)$this->_tag_right#is";
		if (!preg_match_all($reg, $this->_str, $matches))
		{
			return [];
		}

		return $matches[$withTag ? 0 : 1];
	}


	/**
	 * 获取两字符之间的字符串（不支持嵌套）
	 * @author 王崇全
	 * @date
	 * @param string $startStr     起始字符
	 * @param string $endStr       截至字符
	 * @param bool   $withBoundary 是否包含边界
	 * @return bool|string
	 */
	public function GetStrMiddle(string $startStr, string $endStr, bool $withBoundary = false)
	{
		$startPos = mb_strpos($this->_str, $startStr) + ($withBoundary ? 0 : mb_strlen($startStr));

		$offset = 0;
		if ($startStr === $endStr)
		{
			$offset = $startPos + 1;
		}
		$endPos = mb_strpos($this->_str, $endStr, $offset) + ($withBoundary ? mb_strlen($endStr) : 0);

		$cStrL    = $endPos - $startPos;
		$contents = mb_substr($this->_str, $startPos, $cStrL);

		return $contents;
	}

	/**
	 * 获取移除标签后的字符串
	 * @author 王崇全
	 * @date
	 * @return mixed
	 */
	public function GetStrRemovedTags()
	{
		return preg_replace("/$this->_tag_left.+$this->_tag_right/is", "", $this->_str);
	}

	/**
	 * 多字节字符的字符串替换
	 * @author 王崇全
	 * @date
	 * @param string $search  被替换的内容
	 * @param string $replace 替换为的内容
	 * @param string $subject 要做替换处理的字符串
	 * @return string 替换后的字符串
	 */
	public function mb_str_replace($search, $replace, $subject)
	{
		if (is_array($subject))
		{
			$ret = array();
			foreach ($subject as $key => $val)
			{
				$ret[$key] = $this->mb_str_replace($search, $replace, $val);
			}

			return $ret;
		}

		foreach ((array)$search as $key => $s)
		{
			if ($s == '' && $s !== 0)
			{
				continue;
			}
			$r   = !is_array($replace) ? $replace : (array_key_exists($key, $replace) ? $replace[$key] : '');
			$pos = mb_strpos($subject, $s, 0, 'UTF-8');
			while ($pos !== false)
			{
				$subject = mb_substr($subject, 0, $pos, 'UTF-8').$r.mb_substr($subject, $pos + mb_strlen($s, 'UTF-8'), 65535, 'UTF-8');
				$pos     = mb_strpos($subject, $s, $pos + mb_strlen($r, 'UTF-8'), 'UTF-8');
			}
		}

		return $subject;
	}

	/**
	 * @param string $str
	 */
	public function setStr(string $str)
	{
		$this->_str = $str;
	}

	/**
	 * @param string $tag_left
	 */
	public function setTagLeft(string $tag_left)
	{
		$this->_tag_left = $tag_left;
	}

	/**
	 * @param string $tag_right
	 */
	public function setTagRight(string $tag_right)
	{
		$this->_tag_right = $tag_right;
	}

}