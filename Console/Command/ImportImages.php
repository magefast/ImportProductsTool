<?php

namespace Strekoza\ImportTool\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Strekoza\ImportTool\Service\ImportImages as ImportImagesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportImages extends Command
{
    /**
     * @var ImportImagesService
     */
    private $importImagesService;

    /**
     * @var State
     */
    private $state;

    /**
     * @param ImportImagesService $importImagesService
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        ImportImagesService $importImagesService,
        State               $state,
        string              $name = null
    )
    {
        parent::__construct($name);
        $this->importImagesService = $importImagesService;
        $this->state = $state;
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('import:images');
        $this->setDescription('Import Images');
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $this->importImagesService->execute();
    }
}
