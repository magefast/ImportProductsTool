<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Framework\Exception\LocalizedException;
use Strekoza\ImportTool\Helper\Data;
use Zend_Validate_Exception;

class Details
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ImportNewProducts
     */
    private $importNewProducts;

    /**
     * @var Attributes
     */
    private $attributes;

    /**
     * @var CreateAttributes
     */
    private $createAttributes;

    /**
     * @var Report
     */
    private $report;

    /**
     * @param Data $helper
     * @param ImportNewProducts $importNewProducts
     * @param Attributes $attributes
     * @param CreateAttributes $createAttributes
     * @param Report $report
     */
    public function __construct(
        Data              $helper,
        ImportNewProducts $importNewProducts,
        Attributes        $attributes,
        CreateAttributes  $createAttributes,
        Report            $report
    )
    {
        $this->helper = $helper;
        $this->importNewProducts = $importNewProducts;
        $this->attributes = $attributes;
        $this->createAttributes = $createAttributes;
        $this->report = $report;
    }

    /**
     * @throws Zend_Validate_Exception
     */
    public function processing()
    {
        try {
            $shortImportFiles = $this->helper->getContentOfTempImportDir();

            $data = [];

            foreach ($shortImportFiles as $file) {
                $importData = $this->importNewProducts->getImportFileData($file['file_path']);
                if ($importData == null) {
                    continue;
                }

                $firstRow = $importData[0];
                unset($importData);

                foreach ($firstRow as $col) {
                    if ($col != Settings::COLUMN_NAME_PRODUCT_ATTRIBUTES) {
                        $this->importNewProducts->removeCSVcolumn($col);
                    }
                }

                $importData = $this->importNewProducts->getImportData();
                unset($importData[0]);

                foreach ($importData as $d) {
                    $data[] = implode(',', $d);
                }
            }

            $attributesDataForCheck = [];
            $attributesData = $this->attributes->execute($data);
            unset($attributesData[0]);
            foreach ($attributesData as $a) {
                foreach ($a as $key => $value) {
                    if ($value == '') {
                        continue;
                    }
                    $attributesDataForCheck[$key][$value] = $value;
                }
            }
            unset($data);

            $codeName = $this->attributes->getAttributesCodeName();
            foreach ($attributesDataForCheck as $code => $value) {
                if ($this->attributes->checkExistAttribute($code) === false) {
                    $resultCreateAttribute = $this->createAttributes->execute($code, $codeName[$code], $value);
                    if ($resultCreateAttribute === false) {
                        $errorMsg = __("Error with create attribute" . " - " . $code);
                        throw new LocalizedException($errorMsg);
                    }
                    $this->report->setNotice('Added attribute: ' . $code);
                } else {
                    $this->createAttributes->addOptionsExistAttribute($code, $value);
                }
            }
        } catch (LocalizedException $exception) {
            $this->report->setError($exception->getMessage());
            return;
        }
    }
}