<?php

namespace Strekoza\ImportTool\Block\Adminhtml;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\DataObject;

class Run extends Container
{
    /**
     * Block module name
     *
     * @var string|null
     */
    protected $_blockGroup = null;

    /**
     * Controller name
     *
     * @var string
     */
    protected $_controller = 'importtool';

    /**
     * Instantiate save button
     *
     * @return void
     */
    protected function _construct()
    {
        DataObject::__construct();
    }
}
