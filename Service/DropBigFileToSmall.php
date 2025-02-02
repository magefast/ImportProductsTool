<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv as MageCsv;
use Strekoza\ImportTool\Helper\Data as Helper;

class DropBigFileToSmall
{
    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @var MageCsv
     */
    private MageCsv $csv;

    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var Report
     */
    private Report $report;

    /**
     * @var PrepareProductsData
     */
    private PrepareProductsData $prepareProductsData;

    /**
     * @param Settings $settings
     * @param MageCsv $csv
     * @param Helper $helper
     * @param Report $report
     * @param PrepareProductsData $prepareProductsData
     */
    public function __construct(
        Settings            $settings,
        MageCsv             $csv,
        Helper              $helper,
        Report              $report,
        PrepareProductsData $prepareProductsData
    )
    {
        $this->settings = $settings;
        $this->csv = $csv;
        $this->helper = $helper;
        $this->report = $report;
        $this->prepareProductsData = $prepareProductsData;
    }

    /**
     * @param $filename
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function execute($filename)
    {
        $filePath = $filename;
        $tmpImportDir = $this->settings->getTempImportDir();
        $countMaxRow = $this->settings->getCountMaxRow();

        /**
         * Big file drop to small files
         */
        $result = $this->dropBigFileToSmallFilesCSV($filePath, $tmpImportDir, $countMaxRow);
        if ($result === true) {
            $this->report->setNotice(__('Dropped Big import file, to Small files, for further import')->render());
        } else {
            $this->report->setNotice(__('Problem with drop Big import file to Small files')->render());
        }
    }

    /**
     * @param $bigFile
     * @param $tempDir
     * @param int $countMaxRows
     * @return bool
     * @throws Exception
     */
    private function dropBigFileToSmallFilesCSV($bigFile, $tempDir, int $countMaxRows = 300): bool
    {
        try {
            /**
             * Remove old files
             */
            $files = $this->helper->getContentOfTempImportDirAll($tempDir);
            foreach ($files as $f) {
                unlink($f);
            }
            unset($files);

            $mageCsv = $this->csv;
            $mageCsv->setEnclosure('"');
            $mageCsv->setDelimiter(';');

            $fc = $mageCsv->getData($bigFile);

            /**
             * remove exist SKUs
             */
            $fc = $this->prepareProductsData->prepareDataUnsetExistProducts($fc);

            $fn = count($fc);
            $ceil = ceil($fn / $countMaxRows);

            for ($i = 0; $i <= $ceil; $i++) {
                $data = [];

                if ($i != 0) {
                    $data[] = $fc[0];
                }

                for ($s = $i * $countMaxRows; $s <= $countMaxRows + $i * $countMaxRows; $s++) {
                    if (isset($fc[$s])) {
                        $data[] = $fc[$s];
                    }
                }

                if (1 >= count($data)) {
                    continue;
                }

                $f2 = $tempDir . '/' . Settings::DROPPED_IMPORT_FILE_PREFIX . $i . '.csv';

                $mageCsv->appendData($f2, $data);

                $this->report->setNotice($f2);
            }
        } catch (FileSystemException $e) {
            $this->report->setError($e->getMessage());
            return false;
        }

        return true;
    }
}
