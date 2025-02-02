<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Math\Random;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Helper\Data as DataHelper;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory;
use Magento\ImportExport\Model\History;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ConfigInterface;
use Magento\ImportExport\Model\Import\Entity\Factory;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Magento\ImportExport\Model\Source\Import\Behavior\Factory as BehaviorFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;

class ImportCsv extends Import
{
    /**
     * @var Csv
     */
    private $csv;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Csv $csv
     * @param Settings $settings
     * @param Report $report
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param DataHelper $importExportData
     * @param ScopeConfigInterface $coreConfig
     * @param ConfigInterface $importConfig
     * @param Factory $entityFactory
     * @param Data $importData
     * @param CsvFactory $csvFactory
     * @param FileTransferFactory $httpFactory
     * @param UploaderFactory $uploaderFactory
     * @param BehaviorFactory $behaviorFactory
     * @param IndexerRegistry $indexerRegistry
     * @param History $importHistoryModel
     * @param DateTime $localeDate
     * @param array $data
     * @param ManagerInterface|null $messageManager
     * @param Random|null $random
     */
    public function __construct(
        Csv                  $csv,
        Settings             $settings,
        Report               $report,
        LoggerInterface      $logger,
        Filesystem           $filesystem,
        DataHelper           $importExportData,
        ScopeConfigInterface $coreConfig,
        ConfigInterface      $importConfig,
        Factory              $entityFactory,
        Data                 $importData,
        CsvFactory           $csvFactory,
        FileTransferFactory  $httpFactory,
        UploaderFactory      $uploaderFactory,
        BehaviorFactory      $behaviorFactory,
        IndexerRegistry      $indexerRegistry,
        History              $importHistoryModel,
        DateTime             $localeDate,
        array                $data = [],
        ManagerInterface     $messageManager = null,
        Random               $random = null
    )
    {
        $this->csv = $csv;
        $this->settings = $settings;
        $this->report = $report;
        $this->_importExportData = $importExportData;
        $this->_coreConfig = $coreConfig;
        $this->_entityFactory = $entityFactory;

        parent::__construct(
            $logger,
            $filesystem,
            $importExportData,
            $coreConfig,
            $importConfig,
            $entityFactory,
            $importData,
            $csvFactory,
            $httpFactory,
            $uploaderFactory,
            $behaviorFactory,
            $indexerRegistry,
            $importHistoryModel,
            $localeDate,
            $data,
            $messageManager,
            $random
        );
    }

    /**
     * @param $data
     * @throws LocalizedException
     */
    public function execute($data)
    {
        $importFile = $this->prepareImportFile($data);

        if ($importFile == null) {
            return;
        }

        $this->_data['entity'] = 'catalog_product';

        $this->setData(self::FIELD_NAME_VALIDATION_STRATEGY, ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR);
        $this->setData(self::FIELD_NAME_ALLOWED_ERROR_COUNT, 0);

        try {
            $source = $this->getSource($importFile);
            $this->importSource();
        } catch (LocalizedException $e) {
            $this->report->setError($e->getMessage());
        } catch (Exception $e) {
            $this->report->setError(__('Sorry, but the data is invalid or the file is not uploaded.'));
            $this->report->setError($e->getMessage());
        }

        unlink($importFile);
    }

    /**
     * @param $data
     * @return string|null
     */
    private function prepareImportFile($data): ?string
    {
        try {
            $mageCsv = $this->csv;
            $mageCsv->setEnclosure('"');
            $mageCsv->setDelimiter(',');

            $dir = $this->settings->getTempImportDir();
            $fileName = uniqid() . '.csv';
            $filePath = $dir . '/' . $fileName;

            $mageCsv->appendData($filePath, $data);

            return $filePath;
        } catch (Exception $e) {
            $this->report->setError(__('Error create Import file')->render());
            $this->report->setError($e->getMessage());

            return null;
        }
    }

    /**
     * @param $sourceFile
     * @return Import\AbstractSource
     * @throws LocalizedException
     */
    public function getSource($sourceFile)
    {
        try {
            $source = $this->_getSourceAdapter($sourceFile);

            /**
             * Validation
             */
            $validation = $this->checkValidationResult($this->validateSource($source));

            if (isset($validation['result']) && $validation['result'] == 'success') {
                return $source;
            }

            if (isset($validation['errors'])) {
                $errors = $validation['errors'];
                foreach ($errors as $row => $error) {
                    $this->report->setError($error['message']);
                }
            }

            throw new LocalizedException(__('Problem with import - Validation Errors'));
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param $validationResult
     * @return array|string[]
     * @throws LocalizedException
     */
    private function checkValidationResult($validationResult): array
    {
        $result = [];
        $import = $this;
        $errorAggregator = $import->getErrorAggregator();

        if ($import->getProcessedRowsCount()) {
            if ($validationResult) {
                $result['result'] = 'success';
                return $result;
            }
        }

        $result['result'] = 'failed';
        $result['processed_rows_count'] = $import->getProcessedRowsCount();
        $result['invalid_rows_count'] = $errorAggregator->getInvalidRowsCount();
        $result['errors_count'] = $errorAggregator->getErrorsCount();

        foreach ($errorAggregator->getAllErrors() as $all) {
            $error = [];
            $error['row'] = $all->getRowNumber();
            $error['level'] = $all->getErrorLevel();
            $error['code'] = $all->getErrorCode();
            $error['name'] = $all->getColumnName();
            $error['message'] = $all->getErrorMessage();
            $result['errors'][$all->getRowNumber()] = $error;
        }

        return $result;
    }
}
