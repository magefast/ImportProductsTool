<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Zend_Validate_Exception;

class CreateAttributes
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var AttributesList
     */
    private $attributesList;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttributesList $attributesList
     */
    public function __construct(
        EavSetupFactory          $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        AttributesList           $attributesList
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->attributesList = $attributesList;
    }

    /**
     * @param $code
     * @param $label
     * @param array $options
     * @return bool
     * @throws Zend_Validate_Exception
     */
    public function execute($code, $label, array $options = [])
    {
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

            $eavSetup->addAttribute(
                Product::ENTITY,
                $code,
                [
                    'type' => 'varchar',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'frontend' => '',
                    'label' => $label,
                    'input' => 'select',
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'sort_order' => 200,
                    'user_defined' => true,
                    'default' => null,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_in_advanced_search' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_html_allowed_on_front' => false,
                ]
            );

            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                Settings::IMPORT_ATTRIBUTE_SET,
                Settings::IMPORT_ATTRIBUTE_SET_GROUP,
                $code
            );

        } catch (LocalizedException $exception) {
            return false;
        }

        if (count($options) > 0) {
            $this->addOptions($code, $options);
        }

        return true;
    }

    /**
     * @param $code
     * @param $options
     * @throws LocalizedException
     */
    public function addOptions($code, $options)
    {
        $options = array_unique($options);
        if (count($options) > 0) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $attributeId = $eavSetup->getAttributeId('catalog_product', $code);
            $option = ['attribute_id' => $attributeId, 'values' => $options];
            $eavSetup->addAttributeOption($option);
        }
    }

    /**
     * @param $code
     * @param $options
     * @throws LocalizedException
     */
    public function addOptionsExistAttribute($code, $options)
    {
        $needAddOptions = [];
        $attributeList = $this->attributesList->execute();

        if (isset($attributeList[$code]) && isset($attributeList[$code]['options'])) {
            $optionsExist = $attributeList[$code]['options'];
            foreach ($options as $option) {
                if (!isset($optionsExist[$option])) {
                    $needAddOptions[] = $option;
                }
            }
        }

        if (count($needAddOptions) > 0) {
            $this->addOptions($code, $needAddOptions);
        }
    }
}