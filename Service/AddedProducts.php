<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Catalog\Model\Product;

class AddedProducts
{
    /**
     * @var Product
     */
    private $product;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @param $array
     * @return array|null
     */
    public function execute($array): ?array
    {
        $existSku = [];
        foreach ($array as $key => $value) {
            if ($key === 0) {
                continue;
            }

            $sku = $value['sku'];
            $id = $this->product->getIdBySku($sku);
            if ($id) {
                $existSku[$key] = $sku;
            }
        }

        if (count($existSku) > 0) {
            return $existSku;
        }

        return null;
    }
}