<?php

class Excel extends PHPExcel
{
	private $_filePath = null;

	private $_readSheetIndex = null;
	private $_maxRows        = 0;
	private $_maxColumns     = 0;
	private $_objWorksheet   = null;

	private $_isSetRowTitle = array(); //是否已经设置过标题行
	private $_xlsBackDir    = 'backup';//导出文件备份

	function __construct()
	{
		parent::__construct();//构造父类
	}

	/**
	 * 设置读取xls的标签页
	 * @param int $index
	 */
	function SetReadIndex($index)
	{
		$this->_readSheetIndex = $index;
	}

	function StartRead()
	{
		if (!$this->_filePath)
		{
			die("not setFile()");
		}

		PHPExcel_IOFactory::createReader('Excel2007');

		$objReader = PHPExcel_IOFactory::createReaderForFile($this->_filePath);//use excel2007 for 2007 format

		$objPHPExcel = $objReader->load($this->_filePath);

		if (!is_null($this->_readSheetIndex))
		{
			$this->_objWorksheet = $objPHPExcel->getSheet($this->_readSheetIndex);
		}
		else
		{
			$this->_objWorksheet = $objPHPExcel->getActiveSheet();
		}

		$this->_maxRows    = $this->_objWorksheet->getHighestRow();  //取得总行数
		$this->_maxColumns = $this->_objWorksheet->getHighestColumn();
		$this->_maxColumns = PHPExcel_Cell::columnIndexFromString($this->_maxColumns);//总列数
	}

	/*
	 * 开始读取操作!!!!
	 */

	function GetRows()
	{
		return $this->_maxRows;
	}

	function GetCols()
	{
		return $this->_maxColumns;
	}

	function CheckCols($realCols)
	{
		return $realCols == $this->_maxColumns;
	}

	/**
	 * 读取xls内容
	 * @author 王崇全
	 * @date
	 * @param int  $start_row 开始行数
	 * @param int  $max_row   最大行数
	 * @param null $max_col
	 * @return array|null
	 */
	function ReadXls($start_row = 1, $max_row = 10000, $max_col = null)
	{
		$obj = $this->_objWorksheet;
		if (!isset($obj))
		{
			return null;
		}

		$highestRow = $this->_maxRows;

		$highestColumnIndex = $max_col ? $max_col : $this->_maxColumns;
		if (isset($max_row))
		{
			if ($this->_maxRows > $max_row)
			{
				$highestRow = $max_row;
			} //有时候会读到6万多行
			//if($this->_maxColumns > $max_row) $highestRow = $max_row; //有时候会读到6万多行
		}

		$arr_Return = array();
		for ($row = $start_row; $row <= $highestRow; $row++)
		{
			$arr_info = array();

			//注意highestColumnIndex的列数索引从0开始
			for ($col = 0; $col < $highestColumnIndex; $col++)
			{
				$cell = $obj->getCellByColumnAndRow($col, $row)
					->getValue(); //getValue()  getCalculatedValue()
				if ($cell instanceof PHPExcel_RichText)     //富文本转换字符串
				{
					$cell = $cell->__toString();
				}
				if (substr($cell, 0, 1) == '=')
				{ //公式
					$cell = $obj->getCellByColumnAndRow($col, $row)
						->getOldCalculatedValue();
				}

				$arr_info[$col] = is_null($cell) ? '' : $cell;
			}

			$arr_Return[] = $arr_info;
		}

		return $arr_Return;
	}

	function GetValue($row = 1, $col = 0)
	{
		$obj = $this->_objWorksheet;
		if (!isset($obj))
		{
			return null;
		}


		$cell = $obj->getCellByColumnAndRow($col, $row)
			->getValue(); //getValue()  getCalculatedValue()
		if ($cell instanceof PHPExcel_RichText)     //富文本转换字符串
		{
			$cell = $cell->__toString();
		}
		if (substr($cell, 0, 1) == '=')
		{ //公式
			$cell = $obj->getCellByColumnAndRow($col, $row)
				->getOldCalculatedValue();
		}

		return is_null($cell) ? '' : $cell;
	}

	/**
	 * 设置单元格的值
	 * @param int  $row
	 * @param int  $col
	 * @param      $data
	 * @param bool $isV
	 * @return bool
	 * @throws Exception
	 */
	function SetValue($row = 1, $col = 0, $data, $isV = true)
	{
		$obj = $this->_objWorksheet;
		if (!isset($obj))
		{
			E(null, "不是excel对象");
		}

		if (is_array($data))
		{ //数组
			$arrayLevel = array_level($data);
			if ($arrayLevel == 1)
			{ //一维数组

				if ($isV)
				{ //垂直写（$col列）

				}
				else
				{ //水平写（$row行）

				}
			}
			elseif ($arrayLevel == 2)
			{ //二维数组

			}
			else
			{ //其他
				E(null, "数组的维度只能是1或2");
			}
		}
		else
		{ //具体值
			$this->getActiveSheet()
				->setCellValue(PHPExcel_Cell::stringFromColumnIndex($col).$row, $data);

			$this->getActiveSheet()
				->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))
				->setAutoSize(true);
		}

		return true;
	}

	/**
	 * 设置标题
	 * @author 王崇全
	 * @date
	 * @param array $arrTitle eg:$arrWidth = array('A'=>'ID' ,'B'=>'中文', 'D'=>'英文') | array('ID' ,'中文', '英文')
	 * @return void
	 */
	function SetRowTitle($arrTitle)
	{
		$index                        = $this->getActiveSheetIndex();
		$this->_isSetRowTitle[$index] = true;

		if (self::My_Array_Type($arrTitle) == 'assoc')
		{
			foreach ($arrTitle as $Column => $value)
			{
				$this->getActiveSheet()
					->setCellValue($Column.'1', $value);
			}
		}
		else
		{
			$start = 'A';
			for ($i = 0; $i < count($arrTitle); $i++)
			{
				$Column = $start++;
				$this->getActiveSheet()
					->setCellValue($Column.'1', $arrTitle[$i]);
			}
		}
	}

	static function My_Array_Type($arr)
	{
		$c  = count($arr);
		$in = array_intersect_key($arr, range(0, $c - 1));

		if (count($in) == $c)
		{
			return 'index'; //索引数组
		}
		else if (empty($in))
		{
			return 'assoc'; //关联数组
		}
		else
		{
			return 'mix'; //混合数组
		}
	}

	/**
	 * 设置EXCEL每行内容
	 *
	 * @param array $xls_rows
	 *           e.g. $xls_rows = array(
	 *           array('content1','content2','content3'),
	 *           array('A'=>'content1','B'=>'content2','C'=>'content3'),
	 *           ...
	 *           )
	 */
	function SetRows($xls_rows)
	{
		$index = $this->getActiveSheetIndex();
		$n     = $this->_isSetRowTitle[$index] ? 2 : 1;
		foreach ($xls_rows as $row)
		{
			if (self::My_Array_Type($row) == 'assoc')
			{ //关联
				foreach ($row as $Column => $value)
				{
					$this->getActiveSheet()
						->setCellValue($Column.$n, $value);
					$this->getActiveSheet()
						->getStyle($Column.$n)
						->getAlignment()
						->setWrapText(true)
						->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				}
			}
			else
			{
				$start = 'A';
				for ($i = 0; $i < count($row); $i++)
				{
					$Column = $start++;
					$this->getActiveSheet()
						->setCellValue($Column.$n, $row[$i]);
					$this->getActiveSheet()
						->getStyle($Column.$n)
						->getAlignment()
						->setWrapText(true)
						->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				}
			}
			$n++;

			#横向|竖向 对齐方式 setHorizontal | setVertical (PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);  //也可生成EXCEL后手动设置也方便
			# HORIZONTAL_RIGHT | HORIZONTAL_LEFT | HORIZONTAL_CENTER  参考PHPExcel/Style/Alignment.php
			# VERTICAL_RIGHT | VERTICAL_LEFT | VERTICAL_CENTER  参考PHPExcel/Style/Alignment.php

		}
	}

	/**
	 * 设置标题宽度
	 * @author 王崇全
	 * @date
	 * @param array $arrWidth eg:$arrWidth = array('A'=>8 ,'B'=>60, 'C'=>60,'D'=>'auto','E'=>0) | array(8,60,60,0,0)
	 * @return void
	 */
	function SetRowWidth($arrWidth = array())
	{
		if (self::My_Array_Type($arrWidth) == 'assoc')
		{ //关联
			foreach ($arrWidth as $Column => $value)
			{
				if ($value == 'auto' || $value == 0)
				{
					$this->getActiveSheet()
						->getColumnDimension($Column)
						->setAutoSize(true);
				}
				else
				{
					$this->getActiveSheet()
						->getColumnDimension($Column)
						->setWidth($value."pt");
				}
			}
		}
		else
		{
			$start = 'A';
			for ($i = 0; $i < count($arrWidth); $i++)
			{
				$Column = $start++;
				$value  = $arrWidth[$i];
				if ($value == 'auto' || $value == 0)
				{
					$this->getActiveSheet()
						->getColumnDimension($Column)
						->setAutoSize(true);
				}
				else
				{
					$this->getActiveSheet()
						->getColumnDimension($Column)
						->setWidth($value."pt");
				}
			}
		}
	}

	//设置要保存的文件,测试文件是否可以被打开
	function SetFile($filePath)
	{
		$this->_filePath = $filePath;
	}

	//保存文件
	function SaveFile($file_excel = '')
	{
		$this->getProperties()
			->setCreator("Hacfin")
			->setLastModifiedBy("Hacfin")
			->setTitle("Office 2007 XLSX Test Document")
			->setSubject("Office 2007 XLSX Test Document")
			->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Test result file");

		$file_excel = $this->_filePath ? $this->_filePath : $file_excel;
		$objWriter  = PHPExcel_IOFactory::createWriter($this, 'Excel5');
		$objWriter->save($file_excel); //保存xls

		$path_parts = pathinfo($file_excel);
		$dir_bak    = $path_parts["dirname"].'/'.$this->_xlsBackDir; //备份
		if (is_dir($dir_bak))
		{
			$basenameWE     = substr($path_parts["basename"], 0, strlen($path_parts["basename"]) - (strlen($path_parts["extension"]) + 1));
			$file_excel_bak = dirname(__FILE__).'/xls/backup/'.$basenameWE.' '.str_replace(':', '_', date('Y-m-d H:i:s')).'.xls';
			copy($file_excel, $file_excel_bak);
			echo date('H:i:s')." copy($file_excel,$file_excel_bak); ", '<br>'.PHP_EOL;
		}
	}
}