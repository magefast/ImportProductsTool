<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;

class Settings
{
    public const TYPE_URL_FILE = 1;
    public const TYPE_LOCAL_FILE = 2;

    public const IMPORT_DIR_NAME = 'syncimport';
    public const DROPPED_IMPORT_FILE_PREFIX = 'short-import-';
    public const IMPORT_FILE_NAME_DEFAULT = 'imprt.csv';

    public const COUNT_MAX_ROW_PROCESSING_IMPORT = 200;
    public const COUNT_MAX_ROW_PROCESSING_MEDIA_IMPORT = 200;

    public const IMPORT_ATTRIBUTE_SET = 'imported_product';
    public const IMPORT_ATTRIBUTE_SET_GROUP = 'Details Data';

    public const IMPORT_TAX_ID = 2;
    public const IMPORT_TAX_NAME = 'Taxable Goods';
    public const IMPORT_WEIGHT = 0;
    public const IMPORT_VISIBILITY = 'Catalog, Search';
    public const IMPORT_WEBSITES_NAME = 'base';
    public const IMPORT_ATTRIBUTE_IMAGE = 'image_import';

    public const COLUMN_NAME_PRODUCT_ATTRIBUTES = 'details';

    public const IMPORT_COLUMN_SKU_NAME = '_SKU_';

    public const NUM_COL_SKU_VALUE = 2;

    public const IMPORT_COLUMN_ATTRIBUTE_MAPPING =
        [
            'categories' =>
                [
                    'attribute_code' => 'categories'
                ],
            'product_id' =>
                [
                    'attribute_code' => 'product_id_old'
                ],
            'rozetka_old_id' =>
                [
                    'attribute_code' => 'rozetka_old_id'
                ],
            'product_sku' =>
                [
                    'attribute_code' => 'sku'
                ],
            'product_name' =>
                [
                    'attribute_code' => 'name'
                ],
            'product_manufacturer' =>
                [
                    'attribute_code' => 'manufacturer',
                    'type' => 'select'
                ],
            'product_image' =>
                [
                    'attribute_code' => 'image_import'
                ],
            'product_model' =>
                [
                    'attribute_code' => 'model'
                ],
            'product_price' =>
                [
                    'attribute_code' => 'price'
                ],
            'product_description' =>
                [
                    'attribute_code' => 'description'
                ],
            'product_url' =>
                [
                    'attribute_code' => 'url_key'
                ],
            'product_import_id' =>
                [
                    'attribute_code' => 'import_id'
                ]
        ];

    public const IMPORT_PREPARE_LOGIC_CATEGORY_COLUMN_NAME = 'categories';

    public const CATEGORY_ROOT_MAPPING = [
        'code-site' => ['name' => 'Default Category', 'id' => 2]
    ];

    public const CATEGORY_ROOT_MAPPING_DEFAULT_NAME = 'Default Category';
    public const CATEGORY_COL_DELIMITER = '///';
    public const CATEGORY_COL_DELIMITER_FEW_VALUE = '|||';

    public const ATTRIBUTES_DELIMETER = '!?!?!';
    public const ATTRIBUTE_DELIMITER_DETAILS = '|||';
    public const OPTIONS_DELIMITER_FEW_VALUE = '#####';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Set
     */
    private $set;

    /**
     * @var
     */
    private $attributeSet;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Set $set
     * @param Filesystem $filesystem
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Set                  $set,
        Filesystem           $filesystem
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->set = $set;
        $this->filesystem = $filesystem;
    }

    /**
     * @return int
     */
    public function getCountMaxRow(): int
    {
        return self::COUNT_MAX_ROW_PROCESSING_IMPORT;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('importTool/settings/status');
    }

    /**
     * @return string
     * @throws FileSystemException|LocalizedException
     */
    public function getTempImportDir(): string
    {
        $logDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $existDirectory = $logDirectory->isDirectory($this->getImportDir() . '/tmp');
        if (!$existDirectory) {
            $logDirectory->create(self::IMPORT_DIR_NAME . '/tmp');
        }

        return $logDirectory->getAbsolutePath(self::IMPORT_DIR_NAME . '/tmp');
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function getImportDir(): string
    {
        $logDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $existDirectory = $logDirectory->isDirectory(self::IMPORT_DIR_NAME);
        if ($existDirectory != true) {
            $logDirectory->create(self::IMPORT_DIR_NAME);
        }

        return $logDirectory->getAbsolutePath(self::IMPORT_DIR_NAME);
    }

    /**
     * @return mixed
     */
    public function getImportAttributeSetId()
    {
        $attributeSet = $this->set->load(self::IMPORT_ATTRIBUTE_SET, 'attribute_set_name');

        if ($attributeSet->getId()) {
            $this->attributeSet = $attributeSet->getId();
        }

        return $this->attributeSet;
    }

    /**
     * @return string
     */
    public function getImportAttributeSetCode(): string
    {
        return self::IMPORT_ATTRIBUTE_SET;
    }

    /**
     * @return int[]
     */
    public function getAvailableImportType(): array
    {
        return [Settings::TYPE_LOCAL_FILE, Settings::TYPE_URL_FILE];
    }

    /**
     * @return bool
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function checkAvailableImportSource(): bool
    {
        $source = $this->getImportTypeSource();

        $type = $this->getImportType();
        if ($type == Settings::TYPE_LOCAL_FILE) {
            $filePath = $source;
            if (file_exists($filePath)) {
                return true;
            }
        }

        if ($type == Settings::TYPE_URL_FILE) {
            //@todo
        }

        return false;
    }

    /**
     * @return string
     */
    public function getImportTypeSource(): string
    {
        $sourceValue = '';

        $type = $this->getImportType();
        if ($type == Settings::TYPE_LOCAL_FILE) {
            $sourceValue = (string)$this->scopeConfig->getValue('importTool/settings/path_internal_file');
        }

        if ($type == Settings::TYPE_URL_FILE) {
            $sourceValue = (string)$this->scopeConfig->getValue('importTool/settings/link_url_file');
        }

        return $sourceValue;
    }

    /**
     * @return string
     */
    public function getImportType(): string
    {
        return (string)$this->scopeConfig->getValue('importTool/settings/type');
    }

    /**
     * @return string|null
     */
    public function getImportMediaDirPath(): ?string
    {
        $sourceValue = (string)$this->scopeConfig->getValue('importTool/settings/path_media_folder');

        if ($sourceValue != '') {
            return $sourceValue;
        }

        return null;
    }
}
