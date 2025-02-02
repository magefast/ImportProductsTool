<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Zend_Validate_Exception;

class PrepareProductsData
{
    /**
     * @var PrepareUrl
     */
    private PrepareUrl $prepareUrl;

    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @var Collection
     */
    private Collection $productCollection;

    /**
     * @var array
     */
    private array $existSkuList = [];

    /**
     * @var PrepareCategory
     */
    private PrepareCategory $prepareCategory;

    /**
     * @var Report
     */
    private Report $report;

    /**
     * @var Attributes
     */
    private Attributes $attributes;

    /**
     * @var CreateAttributes
     */
    private CreateAttributes $createAttributes;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @param Settings $settings
     * @param Collection $productCollection
     * @param PrepareUrl $prepareUrl
     * @param PrepareCategory $prepareCategory
     * @param Report $report
     * @param Attributes $attributes
     * @param CreateAttributes $createAttributes
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Settings          $settings,
        Collection        $productCollection,
        PrepareUrl        $prepareUrl,
        PrepareCategory   $prepareCategory,
        Report            $report,
        Attributes        $attributes,
        CreateAttributes  $createAttributes,
        CollectionFactory $productCollectionFactory
    )
    {
        $this->settings = $settings;
        $this->productCollection = $productCollection;
        $this->prepareUrl = $prepareUrl;
        $this->prepareCategory = $prepareCategory;
        $this->report = $report;
        $this->attributes = $attributes;
        $this->createAttributes = $createAttributes;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param $data
     * @throws Zend_Validate_Exception
     */
    public function execute(&$data)
    {
        try {
            $settingsColumn = Settings::IMPORT_COLUMN_ATTRIBUTE_MAPPING;
            $columnNameKeyNum = [];

            $this->checkExistSku($data);

            $this->checkExistColumns($data[0]);

            $this->checkAttributeOptionsColumns($data);

            foreach ($data[0] as $key => $value) {
                if (isset($settingsColumn[$value]['attribute_code'])) {
                    $columnNameKeyNum[$key] = $settingsColumn[$value]['attribute_code'];
                }
            }

            foreach ($data as $key1 => $value1) {
                foreach ($value1 as $key => $value) {
                    if (isset($columnNameKeyNum[$key])) {
                        $newKey = $columnNameKeyNum[$key];
                        if ($key1 == 0) {
                            $data[$key1][$newKey] = $newKey;
                        } else {
                            $data[$key1][$newKey] = $value;
                        }
                        unset($data[$key1][$key]);
                    } else {
                        /**
                         * rm _ATTRIBUTE_
                         */
                        if (isset($data[$key1][$key])) {
                            $detailsData[$key1] = $data[$key1][$key];
                            unset($data[$key1][$key]);
                        }
                    }
                }

                if ($key1 == 0) {
                    $data[$key1] = $this->prepareValue($data[$key1], true);
                } else {
                    $data[$key1] = $this->prepareValue($data[$key1]);
                }
            }

            unset($detailsData[0]);

            $this->resortColumn($data);

            $preparedDetails = $this->attributes->execute($detailsData);
            foreach ($data as $key => $value) {
                if (isset($preparedDetails[$key])) {
                    $data[$key] = array_merge($value, $preparedDetails[$key]);
                }
            }

            $keyColumns = [];
            foreach ($data[0] as $key => $value) {
                $keyColumns[$key] = $key;
            }

            foreach ($data as $key => $value) {
                foreach ($keyColumns as $keyColum) {
                    if (!isset($data[$key][$keyColum])) {
                        $data[$key][$keyColum] = '';
                    }
                }
            }
        } catch (LocalizedException $exception) {
            $this->report->setError($exception->getMessage());
            $data = 0;
        }
    }

    /**
     * @param $data
     */
    private function checkExistSku(&$data)
    {
        $existSkuList = $this->getExistSkuList();

        $skuColNum = null;
        foreach ($data[0] as $key => $value) {
            if ($value == Settings::IMPORT_COLUMN_SKU_NAME) {
                $skuColNum = $key;
                break;
            }
        }

        if ($skuColNum == null) {
            return;
        }

        foreach ($data as $key => $value) {
            if ($key == 0) {
                continue;
            }

            if (isset($existSkuList[$value[$skuColNum]])) {
                unset($data[$key]);
                $this->report->setError(__("Exist product with SKU %1 , skipped.", $value[$skuColNum]));
            }
        }
    }

    /**
     * @return array
     */
    private function getExistSkuList(): array
    {
        if (!empty($this->existSkuList)) {
            return $this->existSkuList;
        }

        $collection = $this->productCollection->addAttributeToSelect('sku')->load();

        foreach ($collection as $c) {
            $sku = $c->getSku();
            $this->existSkuList[$sku] = $sku;
        }

        return $this->existSkuList;
    }

    /**
     * @param $data
     * @throws LocalizedException
     */
    private function checkExistColumns($data)
    {
        $columns = Settings::IMPORT_COLUMN_ATTRIBUTE_MAPPING;

        foreach ($columns as $colName => $colValue) {
            if (!in_array($colName, $data)) {
                throw new LocalizedException(__("Not exist column %1 in import file. Please check mapping", $colName));
            }
        }
    }

    /**
     * @param $data
     * @throws LocalizedException
     */
    private function checkAttributeOptionsColumns($data)
    {
        $attributeOptions = [];
        $attributeWithOptions = [];
        foreach ($data as $key => $value) {
            if ($key == 0) {
                foreach (Settings::IMPORT_COLUMN_ATTRIBUTE_MAPPING as $colName => $colVal) {
                    if (isset($colVal['type']) && $colVal['type'] == 'select') {
                        foreach ($value as $n => $r) {
                            if ($colName == $r) {
                                $attributeOptions[$colVal['attribute_code']] = [];
                                $attributeWithOptions[$n] = $colVal['attribute_code'];
                            }
                        }
                    }
                }
                continue;
            }

            foreach ($attributeWithOptions as $k => $m) {
                $attributeOptions[$m][$value[$k]] = $value[$k];
            }
        }

        foreach ($attributeOptions as $code => $options) {
            $this->createAttributes->addOptionsExistAttribute($code, $options);
        }
    }

    /**
     * @param array $values
     * @param bool $first
     * @return array
     * @throws LocalizedException
     */
    private function prepareValue(array &$values, bool $first = false): array
    {
        if ($first === false) {
            $baseFields = $this->baseFields();
        } else {
            $baseFields = $this->baseFieldsFirstRow();
        }

        foreach ($baseFields as $key => $value) {
            $values[$key] = $value;
        }

        if ($first === true) {
            return $values;
        }

        $url_key = $this->prepareUrl->execute($values['url_key']);
        $values['url_key'] = $url_key;
        $values[Settings::IMPORT_PREPARE_LOGIC_CATEGORY_COLUMN_NAME] = $this->prepareCategory->prepareColumnValue($values[Settings::IMPORT_PREPARE_LOGIC_CATEGORY_COLUMN_NAME]);

        return $values;
    }

    /**
     * @return array
     */
    private function baseFields(): array
    {
        $data = [];
        $data['store_view_code'] = 'ru';
        $data['product_websites'] = 'base';
        $data['attribute_set_code'] = $this->settings->getImportAttributeSetCode();
        $data['product_type'] = 'simple';
        $data['visibility'] = Settings::IMPORT_VISIBILITY;
        $data['status'] = Status::STATUS_ENABLED;
        $data['tax_class_name'] = Settings::IMPORT_TAX_NAME;
        $data['tax_class_id'] = Settings::IMPORT_TAX_ID;
        $data['weight'] = Settings::IMPORT_WEIGHT;
        $data['product_websites'] = Settings::IMPORT_WEBSITES_NAME;
        $data['short_description'] = '';
        $data['product_online'] = '1';

        return $data;
    }

    /**
     * @return array
     */
    private function baseFieldsFirstRow(): array
    {
        $data = [];
        $data['store_view_code'] = 'store_view_code';
        $data['attribute_set_code'] = 'attribute_set_code';
        $data['product_type'] = 'product_type';
        $data['visibility'] = 'visibility';
        $data['status'] = 'status';
        $data['tax_class_name'] = 'tax_class_name';
        $data['tax_class_id'] = 'tax_class_id';
        $data['weight'] = 'weight';
        $data['product_websites'] = 'product_websites';
        $data['short_description'] = 'short_description';
        $data['product_online'] = 'product_online';

        return $data;
    }

    /**
     * @param $data
     */
    private function resortColumn(&$data)
    {
        foreach ($data as $key => $value) {
            $sort = [
                'sku' => $value['sku'],
                'store_view_code' => $value['store_view_code'],
                'attribute_set_code' => $value['attribute_set_code'],
                'product_type' => $value['product_type'],
                'categories' => $value['categories'],
                'product_websites' => $value['product_websites'],
                'status' => $value['status'],
                'name' => $value['name'],
                'description' => $value['description'],
                'short_description' => $value['short_description'],
                'weight' => $value['weight'],
                'product_online' => $value['product_online'],
                'tax_class_name' => $value['tax_class_name'],
                'visibility' => $value['visibility'],
                'url_key' => $value['url_key'],
            ];
            $valueSorted = array_merge($sort, $value);
            $data[$key] = $valueSorted;
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function prepareDataUnsetExistProducts($data)
    {
        $allSku = $this->getAllProducts();
        foreach ($data as $k => $d) {
            $sku = $d[Settings::NUM_COL_SKU_VALUE];
            $sku = trim($sku);
            if (isset($allSku[$sku])) {
                unset($data[$k]);
            }
        }
        unset($allSku);

        $dataNew = [];
        foreach ($data as $d) {
            $dataNew[] = $d;
        }
        unset($data);
        return $dataNew;
    }

    /**
     * @return array
     */
    private function getAllProducts(): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('sku');

        $allSku = [];
        foreach ($collection as $c) {
            $allSku[$c->getSku()] = $c->getSku();
        }
        unset($collection);

        return $allSku;
    }
}
