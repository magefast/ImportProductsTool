<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

use Exception;
use Magento\Framework\Exception\LocalizedException;

class ProcessImportFile
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var DropBigFileToSmall
     */
    private $dropBigFileToSmall;

    /**
     * @param Settings $settings
     * @param Report $report
     */
    public function __construct(
        Settings           $settings,
        Report             $report,
        DropBigFileToSmall $dropBigFileToSmall
    )
    {
        $this->settings = $settings;
        $this->report = $report;
        $this->dropBigFileToSmall = $dropBigFileToSmall;
    }

    /**
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute()
    {
        $this->checkRequiredSettings();

        if ($this->settings->getImportType() == Settings::TYPE_URL_FILE) {
            $this->report->setError(__('Import from URL currently not available')->render());
            throw new LocalizedException(__('Import from URL currently not available'));
        }

        if ($this->settings->getImportType() == Settings::TYPE_LOCAL_FILE) {
            $filename = $this->settings->getImportTypeSource();
            if ($this->existImportBigFile($filename)) {
                $this->dropBigFileToSmall->execute($filename);
            }
        }
    }

    /**
     * @throws LocalizedException
     */
    private function checkRequiredSettings()
    {
        #enabled
        if (!$this->settings->isEnabled()) {
            $this->report->setError(__('Import Products Tool Settings - Disabled')->render());
            throw new LocalizedException(__('Import Products Tool Settings - Disabled'));
        }

        #import type
        if ($this->settings->getImportType() == '') {
            $this->report->setError(__('Import Products Tool Settings - Not set Type')->render());
            throw new LocalizedException(__('Import Products Tool Settings - Not set Type'));
        }

        if (!in_array($this->settings->getImportType(), $this->settings->getAvailableImportType())) {
            $this->report->setError(__('Import Products Tool Settings - Incorrect Type')->render());
            throw new LocalizedException(__('Import Products Tool Settings - Incorrect Type'));
        }

        #source file setting exist/available
        if ($this->settings->getImportTypeSource() == '') {
            $this->report->setError(__('Import Products Tool Settings - Not set Import Source file')->render());
            throw new LocalizedException(__('Import Products Tool Settings - Not set Import Source file'));
        }

        if (!$this->settings->checkAvailableImportSource()) {
            $this->report->setError(__('Import Products Tool Settings - Import Source file is not available for processing. Please check file.')->render());
            throw new LocalizedException(__('Import Products Tool Settings - Import Source file is not available for processing. Please check file.'));
        }

        //@todo
        #source file is corrected format
        # check if empty - etc
    }

    /**
     * @throws LocalizedException
     */
    private function existImportBigFile($filename): bool
    {
        if (file_exists($filename)) {
            return true;
        } else {
            $this->report->setError(__('File Import not exist')->render());
            throw new LocalizedException(__("File Import not exist"));
        }
    }
}
