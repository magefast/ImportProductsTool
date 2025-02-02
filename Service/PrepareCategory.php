<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class PrepareCategory
{
    /**
     * @var array
     */
    private $allCategoryNamePath = [];

    /**
     * @var Report
     */
    private $report;

    /**
     * @var CollectionFactory
     */
    private $collectionCategoryFactory;

    /**
     * @param Report $report
     * @param CollectionFactory $collectionCategoryFactory
     */
    public function __construct(Report $report, CollectionFactory $collectionCategoryFactory)
    {
        $this->report = $report;
        $this->collectionCategoryFactory = $collectionCategoryFactory;
    }

    /**
     * @param string $value
     * @return string
     * @throws LocalizedException
     */
    public function prepareColumnValue(string $value)
    {
        $fewValueExplode = explode(Settings::CATEGORY_COL_DELIMITER_FEW_VALUE, $value);
        $categoryRoot = Settings::CATEGORY_ROOT_MAPPING[$fewValueExplode[0]]['name'] ?? Settings::CATEGORY_ROOT_MAPPING_DEFAULT_NAME;
        unset($fewValueExplode[0]);

        $categoriesArray = [];
        foreach ($fewValueExplode as $fv) {
            $cats = [];
            $valueExplodeCat = explode(Settings::CATEGORY_COL_DELIMITER, $fv);
            if (is_array($valueExplodeCat) && isset($valueExplodeCat[0])) {
                $cats[] = $categoryRoot;
                foreach ($valueExplodeCat as $f) {
                    $f = str_replace(',', '&#44;', strval($f));
                    $cats[] = $f;
                }
            }

            $categoriesArray[] = implode('/', $cats);
        }

        return implode(',', $categoriesArray);
    }

    /**
     * @param $rootCategoryId
     * @return array|mixed
     */
    private function getAllCategories($rootCategoryId)
    {
        if (is_array($this->allCategoryNamePath) && isset($this->allCategoryNamePath[$rootCategoryId])) {
            return $this->allCategoryNamePath[$rootCategoryId];
        }

        try {
            $collection = $this->collectionCategoryFactory->create()
                ->addAttributeToSelect(['name', 'path'])
                ->addAttributeToFilter('path', ['like' => '1/' . $rootCategoryId . '/%']);

            $name = [];
            $allCategories = [];
            foreach ($collection as $c) {
                $name[$c->getData('entity_id')] = $c->getData('name');
                $allCategories[$c->getData('path')] = $c->getData('path');
            }
            unset($collection);

            foreach ($allCategories as $key => $value) {
                $allCategories[$key] = $this->prepareNamePath($value, $name, $rootCategoryId);
            }
            unset($name);

            $this->allCategoryNamePath[$rootCategoryId] = $allCategories;
            unset($allCategories);
        } catch (LocalizedException $exception) {
            $this->report->setCriticalError(__('Problem with Category Collection'));
        }

        return $this->allCategoryNamePath;
    }

    /**
     * @param $value
     * @param $array
     * @param $rootCategoryId
     * @return string
     */
    private function prepareNamePath($value, $array, $rootCategoryId)
    {
        $value = str_replace('1/' . $rootCategoryId . '/', '', $value);
        $valueExplode = explode('/', $value);
        $namePathArray = [];
        foreach ($valueExplode as $v) {
            if (isset($array[$v])) {
                $namePathArray[] = $array[$v];
            }
        }

        return implode('/', $namePathArray);
    }
}


