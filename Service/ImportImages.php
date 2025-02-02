<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Strekoza\ImportStockSync\Logger\Logger;

class ImportImages
{
    public const MEDIA_ATTRIBUTES = ['image', 'small_image', 'thumbnail'];

    private $productCountUpdated = 0;

    private $importMediaDirPath;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Action
     */
    private $action;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Report
     */
    private $report;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Settings $settings
     * @param ProductRepositoryInterface $productRepository
     * @param Action $action
     * @param Logger $logger
     * @param Report $report
     */
    public function __construct(
        CollectionFactory          $collectionFactory,
        Settings                   $settings,
        ProductRepositoryInterface $productRepository,
        Action                     $action,
        Logger                     $logger,
        Report                     $report
    )
    {
        $this->productCollectionFactory = $collectionFactory;
        $this->settings = $settings;
        $this->productRepository = $productRepository;
        $this->action = $action;
        $this->logger = $logger;
        $this->report = $report;
    }

    /**
     * @throws LocalizedException|Exception
     */
    public function execute()
    {
        $this->logger->notice('Start import product images');
        $collection = $this->getProductCollection();
        if ($collection->getSize()) {
            $i = Settings::COUNT_MAX_ROW_PROCESSING_MEDIA_IMPORT;
            foreach ($collection as $product) {
                $i--;
                if ($i == 0) {
                    break;
                }
                $imageImportData = $product->getData(Settings::IMPORT_ATTRIBUTE_IMAGE);
                $this->addImages($product, $imageImportData);
            }
        }

        $msg = 'Updated Images for ' . $this->productCountUpdated . ' product(s)';
        $this->report->setNotice($msg);
        $this->logger->notice('Finish import product images');
    }

    /**
     * @return Collection
     */
    private function getProductCollection(): Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(Settings::IMPORT_ATTRIBUTE_IMAGE)
            ->addAttributeToFilter(Settings::IMPORT_ATTRIBUTE_IMAGE, array('neq' => ''));
        return $collection;
    }

    /**
     * @param $product
     * @param string $imageImportData
     * @return void
     * @throws Exception
     */
    private function addImages($product, string $imageImportData = ''): void
    {
        $mediaImportDir = $this->getImportMediaDirPath();

        if ($mediaImportDir == null) {
            return;
        }

        $imageImportData = trim($imageImportData);
        if ($imageImportData == '' || $imageImportData == null) {
            return;
        }

        $images = [];
        $imgData = json_decode($imageImportData, true);

        if (!is_array($imgData) || count($imgData) == 0) {
            return;
        }

        foreach ($imgData as $img) {
            $images[] = $img;
        }

        $firstKey = key($images);
        $p = 0;
        foreach ($images as $key => $value) {
            $imagePath = $mediaImportDir . '/' . $value;
            if (file_exists($imagePath)) {
                $mediaAttributes = null;
                if ($key == $firstKey) {
                    $mediaAttributes = self::MEDIA_ATTRIBUTES;
                }
                try {
                    $product = $product->setStoreId(0)->addImageToMediaGallery($imagePath, $mediaAttributes, false, false);
                } catch (LocalizedException $e) {
                    $msg = 'SKU: ' . $product->getSku() . '. ' . $e->getMessage();
                    $this->logger->error($msg);
                    $this->report->setError($msg);
                    continue;
                }
                $p++;
            }
        }

        if ($p > 0) {
            $id = $product->getId();
            $sku = $product->getSku();

            try {
                $this->productRepository->save($product);
                $this->action->updateAttributes([$id], [Settings::IMPORT_ATTRIBUTE_IMAGE => ''], 0);
                $this->productCountUpdated++;
                $msg = __('Added Images for SKU: ') . $sku;
                $this->logger->notice($msg);
                $this->report->setNotice($msg);
            } catch (CouldNotSaveException $e) {
                $msg = 'SKU: ' . $sku . '. ' . $e->getMessage();
                $this->logger->error($msg);
                $this->report->setError($msg);
            } catch (InputException $e) {
                $msg = 'SKU: ' . $sku . '. ' . $e->getMessage();
                $this->logger->error($msg);
                $this->report->setError($msg);
            } catch (StateException $e) {
                $msg = 'SKU: ' . $sku . '. ' . $e->getMessage();
                $this->logger->error($msg);
                $this->report->setError($msg);
            } catch (NoSuchEntityException $e) {
                $msg = 'SKU: ' . $sku . '. ' . $e->getMessage();
                $this->logger->error($msg);
                $this->report->setError($msg);
            }
        }
    }

    /**
     * @return string|null
     */
    private function getImportMediaDirPath(): ?string
    {
        if ($this->importMediaDirPath != null) {
            return $this->importMediaDirPath;
        }

        $this->importMediaDirPath = $this->settings->getImportMediaDirPath();

        return $this->importMediaDirPath;
    }
}
