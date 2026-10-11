<?php
declare(strict_types=1);

namespace Softaware\MegaMenu\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\MegaMenu\Model\Menu\TreeBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints the menu a store view shows today (after store, status and date filters).
 */
class ListCommand extends Command
{
    public function __construct(
        private readonly TreeBuilder\Proxy $treeBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly Emulation\Proxy $emulation,
        private readonly State $state
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('softaware:megamenu:list')
            ->setDescription('Shows the mega menu of a store view as the storefront renders it today')
            ->addOption('store', 's', InputOption::VALUE_REQUIRED, 'Store view code or ID (default: default store view)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(Area::AREA_FRONTEND);
        } catch (\Exception $e) {
            // area already set
        }
        $code = $input->getOption('store');
        $store = $code !== null ? $this->storeManager->getStore(is_numeric($code) ? (int)$code : $code)
            : $this->storeManager->getDefaultStoreView();
        $this->emulation->startEnvironmentEmulation((int)$store->getId(), Area::AREA_FRONTEND, true);
        try {
            $tree = $this->treeBuilder->build((int)$store->getId());
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
        $output->writeln(sprintf('<info>%s (%s)</info>', $store->getName(), $store->getCode()));
        $this->printNodes($output, $tree['nodes'], 0);
        $output->writeln(sprintf('Cache tags: %d', count($tree['identities'])));
        return Command::SUCCESS;
    }

    private function printNodes(OutputInterface $output, array $nodes, int $level): void
    {
        foreach ($nodes as $node) {
            $extra = [];
            if (!empty($node['label'])) {
                $extra[] = 'label "' . $node['label']['text'] . '"';
            }
            if (!empty($node['panel']['lazy_url'])) {
                $extra[] = 'lazy content';
            } elseif (!empty($node['panel']['content'])) {
                $extra[] = 'content';
            }
            $output->writeln(sprintf(
                '%s- %s%s%s',
                str_repeat('  ', $level),
                $node['title'],
                $node['url'] ? '  ' . $node['url'] : '',
                $extra ? '  [' . implode(', ', $extra) . ']' : ''
            ));
            $this->printNodes($output, $node['children'], $level + 1);
        }
    }
}
