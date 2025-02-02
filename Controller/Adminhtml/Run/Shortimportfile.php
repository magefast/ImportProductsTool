<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Controller\Adminhtml\Run;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Strekoza\ImportTool\Service\ImportNewProducts;
use Strekoza\ImportTool\Service\Report;
use Strekoza\ImportTool\Service\Settings;

class Shortimportfile extends Action
{
    /**
     * @var ImportNewProducts
     */
    private $importNewProducts;

    /**
     * @var ManagerInterface
     */
    private $managerMessage;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var Report
     */
    private $report;

    /**
     * @param Context $context
     * @param ImportNewProducts $importNewProducts
     * @param ManagerInterface $managerMessage
     * @param Http $request
     * @param Report $report
     */
    public function __construct(
        Context           $context,
        ImportNewProducts $importNewProducts,
        ManagerInterface  $managerMessage,
        Http              $request,
        Report            $report
    )
    {
        parent::__construct($context);
        $this->importNewProducts = $importNewProducts;
        $this->managerMessage = $managerMessage;
        $this->request = $request;
        $this->report = $report;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        /**
         * Logic Import Products
         */

        $importFileParam = $this->request->getParam('file_import', Settings::IMPORT_FILE_NAME_DEFAULT);

        $this->importNewProducts->execute($importFileParam);

        $notices = $this->report->getNotices();
        foreach ($notices as $notice) {
            $this->managerMessage->addNoticeMessage($notice);
        }

        $errors = $this->report->getErrors();
        foreach ($errors as $error) {
            $this->managerMessage->addErrorMessage($error);
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
