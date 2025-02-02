<?php

namespace Strekoza\ImportTool\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Strekoza\ImportTool\Service\Settings;

class Data extends AbstractHelper
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param Settings $settings
     */
    public function __construct(
        Context      $context,
        UrlInterface $urlBuilder,
        Settings     $settings
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->settings = $settings;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getProcessingImportFileUrl(): string
    {
        return $this->urlBuilder->getUrl('importtool/run/processimportfile', []);
    }

    /**
     * @return string
     */
    public function getProcessingImportProductImagesUrl(): string
    {
        return $this->urlBuilder->getUrl('importtool/run/processimportproductimages', []);
    }

    /**
     * @param string $value
     * @return string
     */
    public function getShortImportFileUrl(string $value = ''): string
    {
        return $this->urlBuilder->getUrl('importtool/run/shortimportfile', ['file_import' => $value]);
    }

    /**
     * @param string $dirPath
     * @return array
     */
    public function getContentOfTempImportDirAll(string $dirPath = ''): array
    {
        if ($dirPath == '') {
            return [];
        }

        $files = array_diff(scandir($dirPath), ['.', '..']);

        $array = [];
        foreach ($files as $f) {
            $array[$f] = $dirPath . '/' . $f;
        }

        ksort($array);

        return $array;
    }

    /**
     * @return array
     */
    public function getContentOfTempImportDir(): array
    {
        $dirPath = $this->settings->getTempImportDir();

        $files = array_diff(scandir($dirPath), ['.', '..']);

        $array = array();
        foreach ($files as $f) {
            if (strpos($f, '_import.') !== false) {
                continue;
            }

            $numFile = str_replace([Settings::DROPPED_IMPORT_FILE_PREFIX, '.csv'], '', $f);

            $array[$numFile] = [
                'file_name' => $f,
                'file_path' => $dirPath . '/' . $f,
                'file_num' => $numFile
            ];
        }

        ksort($array);

        return $array;
    }

    /**
     * @return int
     */
    public function getCountMaxRow(): int
    {
        return $this->settings->getCountMaxRow();
    }

    /**
     * @param $value
     * @return string
     */
    public function prepareAttributeCode($value): string
    {
        $value = strtolower($value);
        $value = str_replace([' ', '-', '_'], '', $value);
        $value = preg_replace('/[^A-Za-z0-9\-]/', '', $value);
        return $value . '_' . 'import';
    }
}