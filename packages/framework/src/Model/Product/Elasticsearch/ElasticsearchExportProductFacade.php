<?php

namespace Shopsys\FrameworkBundle\Model\Product\Elasticsearch;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticsearchExportProductFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ElasticsearchProductExporter
     */
    protected $exporter;

    public function __construct(Domain $domain, ElasticsearchProductExporter $exporter)
    {
        $this->domain = $domain;
        $this->exporter = $exporter;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function exportAll(OutputInterface $output): void
    {
        foreach ($this->domain->getAll() as $domain) {
            $id = $domain->getId();
            $locale = $domain->getLocale();
            $output->writeln(sprintf('Exporting domain ID %s', $id));
            $this->exporter->export($id, $locale);
            $output->writeln('Exporting finished');
        }
    }
}
