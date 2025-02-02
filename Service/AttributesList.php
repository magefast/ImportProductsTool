<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\Exception\LocalizedException;

class AttributesList
{
    private $attributes;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var Attribute
     */
    private $attributeFactory;

    /**
     * @param Config $eavConfig
     * @param Attribute $attributeFactory
     */
    public function __construct(Config $eavConfig, Attribute $attributeFactory)
    {
        $this->eavConfig = $eavConfig;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function execute()
    {
        if (!is_null($this->attributes)) {
            return $this->attributes;
        }

        $entityId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();
        $attributes = $this->attributeFactory->getCollection()->addFieldToFilter(Set::KEY_ENTITY_TYPE_ID, $entityId);
        $array = [];

        foreach ($attributes as $attribute) {
            if ($attribute->getAttributeCode() == 'status') {
                continue;
            }

            $options = [];
            foreach ($attribute->getOptions() as $option) {
                $label = $option->getData('label');
                $label = trim($label);
                if ($label != '') {
                    $options[$label] = $label;
                }
            }

            $data = array();
            $data['attribute_code'] = $attribute->getAttributeCode();
            $data['attribute_id'] = $attribute->getAttributeId();
            $data['type'] = $attribute->getFrontendInput();
            $data['options'] = $options;
            $array[$attribute->getAttributeCode()] = $data;
        }

        $this->attributes = $array;

        return $this->attributes;
    }
}