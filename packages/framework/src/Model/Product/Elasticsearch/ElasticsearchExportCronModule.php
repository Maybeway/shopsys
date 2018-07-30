<?php

namespace Shopsys\FrameworkBundle\Model\Product\Elasticsearch;

use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\ConsoleOutput;

class ElasticsearchExportCronModule implements SimpleCronModuleInterface
{

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ElasticsearchExportProductFacade
     */
    private $elasticsearchExportProductFacade;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    private $consoleOutput;

    public function __construct(ElasticsearchExportProductFacade $elasticsearchExportProductFacade, ConsoleOutput $consoleOutput)
    {
        $this->elasticsearchExportProductFacade = $elasticsearchExportProductFacade;
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        $this->elasticsearchExportProductFacade->exportAll($this->consoleOutput);
    }
}
