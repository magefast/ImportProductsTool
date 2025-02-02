<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class PrepareUrl extends AbstractDb
{
    /**
     * @var array
     */
    private $allUrlKey = [];

    /**
     * @var Collection
     */
    private $productCollection;

    /**
     * @var Url
     */
    private $productUrl;

    /**
     * @param Context $context
     * @param Collection $productCollection
     * @param Url $productUrl
     * @param null $connectionName
     */
    public function __construct(
        Context    $context,
        Collection $productCollection,
        Url        $productUrl,
                   $connectionName = null
    )
    {
        parent::__construct($context, $connectionName);
        $this->productCollection = $productCollection;
        $this->productUrl = $productUrl;
    }

    /**
     * @param $urlKey
     * @return string
     */
    public function execute($urlKey): string
    {
        $allUrlKey = $this->collectAllUrlKeys();
        $urlKey = $this->productUrl->formatUrlKey($urlKey);

        if (isset($allUrlKey[$urlKey])) {
            for ($x = 1; $x <= 10; $x++) {
                $unique = substr(md5(time()), 0, 3);
                $urlKey = $this->plusUrlIndex($urlKey, $unique);

                if (!isset($allUrlKey[$urlKey])) {
                    return $urlKey;
                }
            }
        }

        return $urlKey;
    }

    /**
     * @param string $urlKey
     * @param $index
     * @return string
     */
    private function plusUrlIndex(string $urlKey, $index)
    {
        return $urlKey .'-'.$index;
    }

    /**
     * @return array
     */
    private function collectAllUrlKeys(): array
    {
        if (!empty($this->allUrlKey)) {
            return $this->allUrlKey;
        }

        $select = $this->getConnection()->select();
        $mainTable = $this->getTable('url_rewrite');
        $select->from(['url_rewrite' => $mainTable], ['request_path']);

        $selectedData = $this->getConnection()->fetchAll($select);

        foreach ($selectedData as $a) {
            if (!empty($a['request_path'])) {
                $requestPath = trim($a['request_path'], '/');

                //@todo - maybe add more elegante solution for str_replace (trim - is incorrect)
                $requestPath2 = str_replace('.html', '', $requestPath);
                $this->allUrlKey[$requestPath2] = $requestPath2;
            }
        }
        unset($selectedData);

        $collection = $this->productCollection->addAttributeToSelect('url_key')->load();
        foreach ($collection as $c) {
            if ($c->getData('url_key')) {
                $requestPath = trim($c->getData('url_key'), '/');
                $requestPath2 = str_replace('.html', '', $requestPath);
                $this->allUrlKey[$requestPath2] = $requestPath2;
            }
        }
        unset($collection);

        return $this->allUrlKey;
    }

    /**
     *
     */
    protected function _construct()
    {
    }
}
