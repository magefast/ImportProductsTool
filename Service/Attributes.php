<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Framework\Exception\LocalizedException;
use Strekoza\ImportTool\Helper\Data;
use Zend_Validate_Exception;

class Attributes
{
    private $attributesCodeName = [];
    private $allAttributesCode = [];
    private $attributeValueOptions = [];

    /**
     * @var string[]
     */
    private $valueAttributeForParsing = array(
        'Основные|',
        'Основные характеристики|',
        'Общие характеристики|',
        'Управление|',
        'Особенности|',
        'Габариты|',
        'Общее|',
        'Для новорожденных|'
    );

    /**
     * @var string[]
     */
    private $valueAttributeForParsingReplace = array(
        'Основные|Основные|' => 'Основные|Основные |',
        'Основные|Основные характеристики|' => 'Основные|Основные характеристики |',
        'Основные|Общие характеристики|' => 'Основные|Общие характеристики |',
        'Основные|Управление|' => 'Основные|Управление |',
        'Основные|Особенности|' => 'Основные|Особенности |',
        'Основные|Габариты|' => 'Основные|Габариты |',
        'Общее|Основные|' => 'Общее|Основные |',
        'Общее|Основные характеристики|' => 'Общее|Основные характеристики |',
        'Общее|Общие характеристики|' => 'Общее|Общие характеристики |',
        'Общее|Управление|' => 'Общее|Управление |',
        'Общее|Особенности|' => 'Общее|Особенности |',
        'Общее|Габариты|' => 'Общее|Габариты |',
        'Общее|Общее|' => 'Общее|Общее |',
    );

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var AttributesList
     */
    private $attributesList;

    /**
     * @var CreateAttributes
     */
    private $createAttributes;

    /**
     * @param Data $helper
     * @param AttributesList $attributesList
     * @param CreateAttributes $createAttributes
     */
    public function __construct(Data $helper, AttributesList $attributesList, CreateAttributes $createAttributes)
    {
        $this->helper = $helper;
        $this->attributesList = $attributesList;
        $this->createAttributes = $createAttributes;
    }

    /**
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function execute($data = [])
    {
        $array = [];
        foreach ($data as $key => $value) {
            if ($value == '' || $value == null) {
                continue;
            }
            $data = $this->parsingCSVdata($value);
            if (count($data) == 0) {
                continue;
            }
            $array[$key] = $data;
        }

        $dataColumn = [];
        foreach ($array as $key => $value) {
            foreach ($value as $key2 => $value2) {
                if (!isset($value2['code']) || empty($value2['code'])) {
                    $code = $this->helper->prepareAttributeCode($key2);
                } else {
                    $code = $value2['code'];
                }

                $this->attributesCodeName[$code] = $value2['label'];

                if (!isset($this->allAttributesCode[$code])) {
                    $this->allAttributesCode[$code] = $code;
                }

                foreach ($value2['value'] as $v) {
                    $this->attributeValueOptions[$code][$v] = $v;
                }

                $dataColumn[$key][$code] = implode(',', $value2['value']);
            }
        }
        unset($array);

        $dataColumnCSV = [];
        foreach ($dataColumn as $key => $value) {
            foreach ($value as $code => $valueOption) {
                foreach ($this->allAttributesCode as $columnCode) {
                    if (isset($dataColumn[$key][$columnCode])) {
                        $dataColumnCSV[$key][$columnCode] = $dataColumn[$key][$columnCode];
                    } else {
                        $dataColumnCSV[$key][$columnCode] = '';
                    }
                }
            }
        }
        unset($dataColumn);

        /**
         * first row
         */
        foreach ($this->allAttributesCode as $columnCode) {
            $dataColumnCSV[0][$columnCode] = $columnCode;
        }

        foreach ($this->attributesCodeName as $code => $label) {
            if ($this->checkExistAttribute($code) === false) {
                $resultCreateAttribute = $this->createAttributes->execute($code, $label, $this->attributeValueOptions[$code]);
                if ($resultCreateAttribute === false) {
                    $errorMsg = __("Error with create attribute" . " - " . $code);
                    throw new LocalizedException($errorMsg);
                }
            } else {

                $this->createAttributes->addOptionsExistAttribute($code, $this->attributeValueOptions[$code]);
            }
        }

        return $dataColumnCSV;
    }

    /**
     * @param $data
     * @return array
     */
    private function parsingCSVdata($data)
    {
        $attributesArray = [];

        foreach ($this->valueAttributeForParsingReplace as $key => $value) {
            $data = str_replace($key, $value, $data);
        }

        /*
        foreach ($this->valueAttributeForParsing as $v) {
            $data = str_replace($v, '*****', $data);
        }
        //@todo - add support for subtitle
        */

        if (str_contains($data, Settings::ATTRIBUTES_DELIMETER)) {
            $j = explode(Settings::ATTRIBUTES_DELIMETER, $data);
        } else {
            $j[] = $data;
        }

        foreach ($j as $k) {
            if ($k != '') {
                preg_replace("/\r|\n/", "", $k);
                $k = str_replace(array("\r", "\n"), '', $k);
                $k = str_replace(' ', ' ', $k);
                $k = str_replace(' | ', ' / ', $k);

                $value = explode(Settings::ATTRIBUTE_DELIMITER_DETAILS, $k);
                $attributeCode = trim($value[0]);
                $attributeName = trim($value[1]);
                $attributeValue = strval($value[2]);
                $attributeValue = trim($attributeValue, Settings::OPTIONS_DELIMITER_FEW_VALUE);

                $attributeValue = $this->prepareValue($attributeValue, $attributeName);

                $attributeValueArray = [];
                $attributeValueArray[] = $attributeValue;
                //@todo - add support for multiselect - few values

                /*
                if (count($value) > 2) {
                    unset($value[0]);

                    $tempValue = [];
                    foreach ($value as $vv) {
                        $tempValue[] = trim($vv);
                    }
                    $attributeValue = implode(', ', $tempValue);
                }
                */

                $attributesArray[trim($attributeCode)] = ['code' => $attributeCode, 'label' => $attributeName, 'value' => $attributeValueArray];
            }
        }

        return $attributesArray;
    }

    /**
     * @param $code
     * @return bool
     * @throws LocalizedException
     */
    public function checkExistAttribute($code): bool
    {
        $attributeList = $this->attributesList->execute();
        if (isset($attributeList[$code])) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getAttributesCodeName()
    {
        return $this->attributesCodeName;
    }

    /**
     * @param array $valueArray
     * @return array
     */
//    private function prepareOptionsValue(array $valueArray)
//    {
//        foreach ($valueArray as $key => $value) {
//            if (is_string($value)) {
//                $value = $this->prepareValue($value);
//            }
//            $valueArray[$key] = $value;
//        }
//
//        return $valueArray;
//    }

    /**
     * @param $valueAttribute
     * @param $nameAttribute
     * @return string
     */
    private function prepareValue($valueAttribute, $nameAttribute): string
    {
        if ($nameAttribute == 'Температура') {
            $valueAttribute = $valueAttribute . '°';
            $valueAttribute = str_replace('--', '-', $valueAttribute);
            return $valueAttribute;
        }

        return $valueAttribute;
    }
}
