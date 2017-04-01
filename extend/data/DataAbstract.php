<?php
namespace data;

abstract class DataAbstract
{
	protected $m_sorPath;      // 有后缀或无后缀
	protected $m_sorPathNoExt; // 无后缀
	protected $m_bHandle = false;

	public function __construct($sorPath)
	{
		$this->m_sorPath = $sorPath;

		if (!is_file($sorPath))
		{
			$this->m_sorPathNoExt = null;
		}
		else
		{
			$arr = pathinfo($sorPath);

			$this->m_sorPathNoExt = $arr['dirname'].'/'.$arr['filename'];
		}
	}

	abstract public function create_thumbImage($desWidth, $desHeight);

	abstract protected function check_handler();
}