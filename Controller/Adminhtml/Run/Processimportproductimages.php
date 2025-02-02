<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Controller\Adminhtml\Run;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Strekoza\ImportTool\Service\ImportImages;
use Strekoza\ImportTool\Service\Report;

class Processimportproductimages extends Action
{
    /**
     * @var ManagerInterface
     */
    private $managerMessage;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var ImportImages
     */
    private $importImages;

    /**
     * @param Context $context
     * @param ImportImages $importImages
     * @param ManagerInterface $managerMessage
     * @param Report $report
     */
    public function __construct(
        Context          $context,
        ImportImages     $importImages,
        ManagerInterface $managerMessage,
        Report           $report
    )
    {
        parent::__construct($context);
        $this->importImages = $importImages;
        $this->managerMessage = $managerMessage;
        $this->report = $report;
    }

    /**
     * @return Redirect
     * @throws Exception
     */
    public function execute()
    {
        try {
            /**
             * Logic
             */
            $this->importImages->execute();

            $notices = $this->report->getNotices();
            foreach ($notices as $notice) {
                $this->managerMessage->addNoticeMessage($notice);
            }

            $errors = $this->report->getErrors();
            foreach ($errors as $error) {
                $this->managerMessage->addErrorMessage($error);
            }

        } catch (LocalizedException $e) {
            $this->managerMessage->addError($e->getMessage());
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}