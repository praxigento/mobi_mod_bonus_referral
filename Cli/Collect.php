<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Cli;

use Praxigento\BonusReferral\Api\Service\Bonus\Collect\Request as ARequest;

/**
 * Collect and pay referral bonus after delay.
 */
class Collect
    extends \Praxigento\Core\App\Cli\Cmd\Base
{
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $conn;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect */
    private $servCollect;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $manObj,
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\BonusReferral\Service\Bonus\Collect $servCollect
    ) {
        parent::__construct(
            $manObj,
            'prxgt:bonus:referral:collect',
            'Collect and pay referral bonus after delay.'
        );
        $this->resource = $resource;
        $this->conn = $resource->getConnection();
        $this->servCollect = $servCollect;
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $output->writeln("<info>Start referral bonus collection.<info>");
        /* wrap all DB operations with DB transaction */
        $this->conn->beginTransaction();
        try {
            $req = new ARequest();
            $this->servCollect->exec($req);
            $this->conn->commit();
        } catch (\Throwable $e) {
            $output->writeln('<info>Command \'' . $this->getName() . '\' failed. Reason: '
                . $e->getMessage() . '.<info>');
            $this->conn->rollBack();
        }
        $output->writeln('<info>Command \'' . $this->getName() . '\' is completed.<info>');
    }
}