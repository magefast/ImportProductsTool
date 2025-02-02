<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Controller\Adminhtml\Run;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Strekoza\ImportTool\Service\Details;
use Strekoza\ImportTool\Service\ProcessImportFile as ServiceProcessImportFile;
use Strekoza\ImportTool\Service\Report;

class Processimportfile extends Action
{
    /**
     * @var ServiceProcessImportFile
     */
    private ServiceProcessImportFile $processImportFile;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $managerMessage;

    /**
     * @var Report
     */
    private Report $report;

    /**
     * @var Details
     */
    private Details $details;

    /**
     * @param Context $context
     * @param ServiceProcessImportFile $processImportFile
     * @param ManagerInterface $managerMessage
     * @param Report $report
     * @param Details $details
     */
    public function __construct(
        Context                  $context,
        ServiceProcessImportFile $processImportFile,
        ManagerInterface         $managerMessage,
        Report                   $report,
        Details                  $details
    )
    {
        parent::__construct($context);
        $this->processImportFile = $processImportFile;
        $this->managerMessage = $managerMessage;
        $this->report = $report;
        $this->details = $details;
    }

    /**
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            /**
             * Logic
             */
            $this->processImportFile->execute();

            /**
             * Details attribute
             */
            $this->details->processing();

            $notices = $this->report->getNotices();
            foreach ($notices as $notice) {
                $this->managerMessage->addNoticeMessage($notice);
            }

            $errors = $this->report->getErrors();
            foreach ($errors as $error) {
                $this->managerMessage->addErrorMessage($error);
            }

        } catch (LocalizedException $e) {

            //var_dump($e->getMessage());
            $this->managerMessage->addError($e->getMessage());

            //die('---');
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
