<?php
require_once ROOT_FW_PATH . 'application/function/includes/PHPExcel/PHPExcel.php';

trait Files
{
    /**
     * 导出excel
     * @param array $datas  excel数据
     * @param string $filename     文件绝对路径
     * @param null $title   表格名字
     * @param array $config 配置['width', 'height', 'append::图片偏移量']
     * @return mixed
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function exportExcel(array $datas, $filename, $title=null, array $config=array())
    {
        $width = isset($config['width']) ? $config['width'] : 15;
        $height = isset($config['height']) ? $config['height'] : 15;
        $append = isset($config['append']) ? $config['append'] : 50;

        if(strtolower(substr(PHP_OS,0,3))=='win') {
            $filename = iconv('utf-8', 'gbk', $filename);
        }
        $title = is_null($title) ? pathinfo($filename, PATHINFO_FILENAME) : $title;
        $extension = array('xls' => 'Excel5', 'xlsx' => 'Excel2007');
        $fileType = $extension[pathinfo($filename, PATHINFO_EXTENSION)];
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle($title);

        $activeSheet = $objPHPExcel->getActiveSheet();
        foreach ($datas as $k=>$data)
        {
            $tmpCellName = $cellName;
            foreach ($data as $ik=>$item)
            {
                $columnName = array_shift($tmpCellName);
                $activeName = $columnName . ($k+1);
                if(strpos($ik, 'image::') === 0){
                    $image = new PHPExcel_Worksheet_Drawing();
                    //设置图片路径
                    $image->setPath($item);
                    //设置图片高度
                    $image->setWidth($width+$append);
                    $image->setHeight($height+$append);
                    //设置图片要插入的单元格
                    $image->setCoordinates($activeName);
                    $image->setWorksheet($objPHPExcel->getActiveSheet());
                }else{
                    $activeSheet->setCellValue($activeName, (string) $item);
                }
                //设置单元格宽度/高度
                $activeSheet->getColumnDimension($columnName)->setWidth($width);
                $activeSheet->getRowDimension($k+1)->setRowHeight($height);
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $fileType);
        $objWriter->save($filename);
        return $filename;
    }

    /**
     * 导出excel处理
     * @param callable $callback    回调函数
     */
    public function exportHandle(callable $callback)
    {
        try{
            $callback();
        }catch (\Exception $e){
            cls_output::out('E00002', '导出文件失败');
        }
    }


    /**
     * 上传excel, 支持xlsx/xls
     *      未测试
     * @param $data
     * @param $savePath
     * @param null $fileName
     * @return array
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function implodeExcel($data, $savePath, $fileName = null)
    {
        $newName = $this->upload($data, $savePath, $fileName);
        $ex = pathinfo($data['name'], PATHINFO_EXTENSION);

        if ($ex == 'xlsx') {
            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        } elseif ($ex == 'xls') {
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
        }
        /** @var PHPExcel_Reader_Abstract $objReader */
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($newName);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData[$row][] = (string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }

        @unlink($newName);
        return $excelData;
    }

    /**
     * 上传文件
     * @param $name
     * @param $tmpName
     * @param string $savePath 文件保存路径
     * @param null $fileName 文件名
     * @return null|string      文件路径
     */
    public function upload($name, $tmpName, $savePath, $fileName = null)
    {
        $ex = pathinfo($name, PATHINFO_EXTENSION);
        //$savePath = "framework/data/temp/excel/" . date("Ymd");
        if (!is_dir($savePath)) {
            @mkdir($savePath, 0777, true);
        }

        $newName = is_null($fileName) ? time() . rand(1000, 9999) : $fileName;
        $newName = $savePath . '/' . $newName . "." . $ex;
        @move_uploaded_file($tmpName, $newName);
        return $newName;
    }


}

