<?php
declare(strict_types=1);

namespace CommerceGears\MegaMenu\Console\Command;

use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\MegaMenu\Model\CategoryImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds one menu item per top-level category (skips categories that already have one).
 */
class ImportCategoriesCommand extends Command
{
    public function __construct(
        private readonly CategoryImporter\Proxy $importer,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('commercegears:megamenu:import-categories')
            ->setDescription('Adds a mega menu item for each top-level category of a store view')
            ->addOption('store', 's', InputOption::VALUE_REQUIRED, 'Store view code or ID whose root category is used (default: default store view)')
            ->addOption('only-this-store', null, InputOption::VALUE_NONE, 'Assign the new items to that store view only (default: all store views)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $code = $input->getOption('store');
        $storeId = $code !== null
            ? (int)$this->storeManager->getStore(is_numeric($code) ? (int)$code : $code)->getId()
            : 0;
        $created = $this->importer->import($storeId, $input->getOption('only-this-store') && $storeId ? [$storeId] : []);
        $output->writeln($created
            ? sprintf('<info>%d item(s) added (IDs %s).</info>', count($created), implode(', ', $created))
            : 'Every top-level category already has a menu item.');
        return Command::SUCCESS;
    }
}
