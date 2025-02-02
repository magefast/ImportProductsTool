<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Strekoza\ImportTool\Service\ImportImages;

class Images
{
    private LoggerInterface $logger;
    private ImportImages $importImages;

    public function __construct(
        LoggerInterface $logger,
        ImportImages    $importImages
    )
    {
        $this->logger = $logger;
        $this->importImages = $importImages;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function execute()
    {
        $this->logger->info('Import Images - START');

        $this->importImages->execute();

        $this->logger->info('Import Images - FINISH');

        return $this;
    }
}
