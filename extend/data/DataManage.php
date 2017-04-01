<?php
namespace data;

use app\api\model\File;
use app\api\model\TaskConvert;

/**
 * Class DataManage
 * @property DataImage|DataVideo|DataPdf $m_actionCls
 * @package data
 */
class DataManage
{
	private $m_actionCls;
	private $m_sorPath;
	private $m_realName;
	private $m_type;

	public function __construct($sorPath, $type, $realName)
	{
		$this->m_sorPath  = $sorPath;
		$this->m_type     = $type;
		$this->m_realName = $realName;
		$this->load();
	}

	public function create_thumbImage($desWidth, $desHeight, $fromCvt = false)
	{
		if (isset($this->m_actionCls))
		{
			return $this->m_actionCls->create_thumbImage($desWidth, $desHeight, $fromCvt);
		}
	}

	private function load()
	{
		switch ($this->m_type)
		{
			case File::TYPE_PICTURE:
			{
				$this->m_actionCls = new DataImage($this->m_sorPath);
			}
			break;
			case File::TYPE_VIDEO:
			{
				$this->m_actionCls = new DataVideo($this->m_sorPath);
			}
			break;
			case File::TYPE_TEXT:
			{
				//doc类型分为office、pdf
				$extName = File::GetExt($this->m_sorPath);
				if (strcasecmp($extName, 'pdf') == 0)
				{
					$this->m_actionCls = new DataPdf($this->m_sorPath);
				}
				else
				{
					// _cvt_.pdf
					$strCvtPath = $this->m_sorPath.TaskConvert::CONVERT_PDF.'.pdf';
					if (is_file($strCvtPath))
					{
						$this->m_actionCls = new DataPdf($strCvtPath);
					}
				}
			}
			break;
			default:
			break;
		}
	}
}