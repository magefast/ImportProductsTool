<?php

namespace Strekoza\ImportTool\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Strekoza\ImportTool\Service\Settings;

class Type implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = [
            'label' => __('URL file'),
            'value' => Settings::TYPE_URL_FILE
        ];
        $options[] = [
            'label' => __('Local file'),
            'value' => Settings::TYPE_LOCAL_FILE
        ];

        return $options;
    }
}
