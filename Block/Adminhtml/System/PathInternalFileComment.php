<?php

namespace Strekoza\ImportTool\Block\Adminhtml\System;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Element\AbstractBlock;
use Strekoza\ImportTool\Service\Settings;

class PathInternalFileComment extends AbstractBlock implements CommentInterface
{
    /**
     * @inheritDoc
     */
    public function getCommentText($elementValue)
    {
        $dir = DirectoryList::VAR_DIR . '/' . Settings::IMPORT_DIR_NAME;
        return "File should be in dir " . $dir;
    }
}