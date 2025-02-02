<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */


// @todo

namespace Strekoza\ImportTool\Service;

use Magento\ImportExport\Model\Import;

class Validate
{
    /**
     * @var Import
     */
    private $importModel;

    /**
     * @param Import $importModel
     */
    public function __construct(Import $importModel)
    {
        $this->importModel = $importModel;

    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(string $filePath)
    {



        $errorAggregator = $this->importModel->getErrorAggregator();
        $errorAggregator->initValidationStrategy(
            $this->importModel->getData(Import::FIELD_NAME_VALIDATION_STRATEGY),
            $this->importModel->getData(Import::FIELD_NAME_ALLOWED_ERROR_COUNT)
        );
    }


    private function processValidationResult($validationResult, $resultBlock)
    {
        $import = $this->getImport();
        $errorAggregator = $import->getErrorAggregator();

        if ($import->getProcessedRowsCount()) {
            if ($validationResult) {
                $this->addMessageForValidResult($resultBlock);
            } else {
                $resultBlock->addError(
                    __('Data validation failed. Please fix the following errors and upload the file again.')
                );

                if ($errorAggregator->getErrorsCount()) {
                    $this->addMessageToSkipErrors($resultBlock);
                }
            }
            $resultBlock->addNotice(
                __(
                    'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                    $import->getProcessedRowsCount(),
                    $import->getProcessedEntitiesCount(),
                    $errorAggregator->getInvalidRowsCount(),
                    $errorAggregator->getErrorsCount()
                )
            );

            $this->addErrorMessages($resultBlock, $errorAggregator);
        } else {
            if ($errorAggregator->getErrorsCount()) {
                $this->collectErrors($resultBlock);
            } else {
                $resultBlock->addError(__('This file is empty. Please try another one.'));
            }
        }
    }
}