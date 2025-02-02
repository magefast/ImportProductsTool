<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv as MageCsv;

class ImportNewProducts
{
    private $csv;
    private $settings;

    /**
     * @var array
     */
    private $csvImportData = [];

    /**
     * @var Report
     */
    private $report;

    /**
     * @var PrepareProductsData
     */
    private $prepareProductsData;

    /**
     * @var ImportCsv
     */
    private $importCsv;

    /**
     * @var AddedProducts
     */
    private $addedProducts;

    /**
     * @param MageCsv $csv
     * @param Settings $settings
     * @param Report $report
     * @param PrepareProductsData $prepareProductsData
     * @param ImportCsv $importCsv
     * @param AddedProducts $addedProducts
     */
    public function __construct(
        MageCsv             $csv,
        Settings            $settings,
        Report              $report,
        PrepareProductsData $prepareProductsData,
        ImportCsv           $importCsv,
        AddedProducts       $addedProducts
    )
    {
        $this->csv = $csv;
        $this->settings = $settings;
        $this->report = $report;
        $this->prepareProductsData = $prepareProductsData;
        $this->importCsv = $importCsv;
        $this->addedProducts = $addedProducts;
    }

    /**
     * @param string $filePath
     * @throws LocalizedException|\Zend_Validate_Exception
     */
    public function execute(string $filePath = '')
    {
        $filePath = $this->settings->getTempImportDir() . '/' . $filePath;

        $importData = $this->getImportFileData($filePath);

        if ($importData == null) {
            throw new LocalizedException(__("File Import empty"));
        }

        $tempData = [];
        $tempData[0] = $importData[0];

        try {
            $this->prepareProductsData->execute($importData);
        } catch (LocalizedException $exception) {
            $this->report->setError($exception->getMessage());
            return;
        }

        if (empty($importData)) {
            $this->report->setError(__('Empty import data')->render());
            return;
        }

        $keys = $importData[0];
        //unset($importData[0]);

        $this->importCsv->execute($importData);

        $addedSkus = $this->addedProducts->execute($importData);
        if ($addedSkus != null) {
            foreach ($importData as $key => $value) {
                if ($key === 0) {
                    continue;
                }

                $result = in_array($value['sku'], $addedSkus);
                if ($result === true) {
                    unset($importData[$key]);
                } else {
                    $tempData[$key] = $importData[$key];
                }
            }

            foreach ($addedSkus as $sku) {
                $this->report->setNotice('Added SKU - ' . $sku);
            }
        }

        try {
            if (!empty($addedSkus)) {
                if (count($tempData) > 1) {
                    $importData = array_merge([$keys], $importData);
                    $this->csv->appendData($filePath, $importData);
                } else {
                    unlink($filePath);
                }
            }
        } catch (LocalizedException $e) {
            $this->report->setError($e->getMessage());
        }
    }

    /**
     * @throws LocalizedException
     */
    public function getImportFileData(string $filePath = ''): ?array
    {
        if (!file_exists($filePath)) {
            $this->report->setError(__('File Import not exist')->render());
            throw new LocalizedException(__("File Import not exist"));
        }

        try {
            $mageCsv = $this->csv;
            $mageCsv->setEnclosure('"');
            $mageCsv->setDelimiter(';');

            $this->csvImportData = $mageCsv->getData($filePath);

            $this->mergeCSVcolumn('_IMAGE_', '_IMAGES_');

            /**
             * Remove column that not used for Import
             */
            $this->removeCSVcolumn('_STOCK_STATUS_ID_');
            $this->removeCSVcolumn('_QUANTITY_');
            $this->removeCSVcolumn('_STATUS_');
            $this->removeCSVcolumn('_JAN_');
            //$this->removeCSVcolumn('_NAME_ latishskom_');
            //$this->removeCSVcolumn('_DESCRIPTION_latishskom_');
            // $this->removeCSVcolumn('_ATTRIBUTES_');

            return $this->csvImportData;
        } catch (FileSystemException $e) {
            $this->report->setError($e->getMessage());
            return null;
        }
    }

    /**
     * @param $colMain
     * @param $colToMerge
     */
    private function mergeCSVcolumn($colMain, $colToMerge)
    {
        $colMainNum = null;
        $colToMergeNum = null;

        foreach ($this->csvImportData[0] as $key => $value) {
            if ($colMain == $value) {
                $colMainNum = $key;
            }
            if ($colToMerge == $value) {
                $colToMergeNum = $key;
            }
        }

        if ($colMainNum == null || $colToMergeNum == null) {
            return;
        }

        foreach ($this->csvImportData as $key => $value) {
            if ($key == 0) {
                continue;
            }
            $tempValue = [];
            foreach ($value as $key2 => $value2) {
                if ($colMainNum == $key2) {
                    if ($value2 != '') {
                        $tempValue[] = $value2;
                    }
                }
                if ($colToMergeNum == $key2) {
                    if ($value2 != '') {
                        $value2 = explode(',', $value2);
                        foreach ($value2 as $v) {
                            $tempValue[] = $v;
                        }
                    }
                }
            }

            $this->csvImportData[$key][$colMainNum] = implode(',', $tempValue);
            $this->removeCSVcolumn('_IMAGES_');
        }
    }

    /**
     * @param $columnName
     * @return false|void
     */
    public function removeCSVcolumn($columnName)
    {
        if ($columnName == '') {
            return false;
        }

        $data = array();

        foreach ($this->csvImportData[0] as $key => $value) {
            if ($value == $columnName) {
                $columnNum = $key;
            }
        }

        foreach ($this->csvImportData as $key => $value) {
            if (isset($columnNum)) {
                unset($value[$columnNum]);
            }
            $data[$key] = $value;
        }

        $this->csvImportData = $data;
        unset($data);
    }

    public function getImportData()
    {
        return $this->csvImportData;
    }
}
