<?php

namespace Drupal\reportes\Controller;

class PhpExcelController
{

public static function ExportExcel()
{
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=my_excel_filename.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        flush();

        require_once 'sites/libraries/phpexcel/Classes/PHPExcel.php';
        require_once 'sites/libraries/phpexcel/Classes/PHPExcel/Writer/Excel2007.php';

        $spreadsheet = new \PHPExcel();

        //Set properties
        $spreadsheet->getProperties()
                ->setCreator('Test')
                ->setLastModifiedBy('Test')
                ->setTitle("PHPExcel Demo")
                ->setLastModifiedBy('Test')
                ->setDescription('A demo to show how to use PHPExcel to manipulate an Excel file')
                ->setSubject('PHP Excel manipulation')
                ->setKeywords('excel php office phpexcel lakers')
                ->setCategory('programming');

        //Add some data
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();

        //Rename sheet
        $worksheet->setTitle('My File name');

         /*
         * TITLE
         */
        //Set style Title
        $styleArrayTitle = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => '161617'),
                'size' => 12,
                'name' => 'Verdana'
        ));

        $worksheet->getCell('A1')->setValue('TEST PHPEXCEL');
        $worksheet->getStyle('A1')->applyFromArray($styleArrayTitle);

        /*
         * HEADER
         */
        //Set Background        
        $worksheet->getStyle('A3:E3')
                ->getFill()
                ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('085efd');

        //Set style Head
        $styleArrayHead = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'ffffff'),
        ));

        $worksheet->getCell('A3')->setValue('C1');
        $worksheet->getCell('B3')->setValue('C2');
        $worksheet->getCell('C3')->setValue('C3');

        $worksheet->getStyle('A3:E3')->applyFromArray($styleArrayHead);

        for ($i = 4; $i < 10; $i++) {  
            $worksheet->setCellValue('A' . $i, $i);
            $worksheet->setCellValue('B' . $i, 'Test C2');
            $worksheet->setCellValue('C' . $i, 'Test C3');
        }

        $writer = new \PHPExcel_Writer_Excel2007($spreadsheet);

        ob_end_clean();
        $writer->save('php://output');
        exit();

}
}

?>