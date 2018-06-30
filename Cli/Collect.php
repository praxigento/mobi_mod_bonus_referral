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
    /** @var \Praxigento\Core\Api\App\Repo\Transaction\Manager */
    private $manTrans;
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect */
    private $servCollect;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $manObj,
        \Praxigento\Core\Api\App\Repo\Transaction\Manager $manTrans,
        \Praxigento\BonusReferral\Service\Bonus\Collect $servCollect
    ) {
        parent::__construct(
            $manObj,
            'prxgt:bonus:referral:collect',
            'Collect and pay referral bonus after delay.'
        );
        $this->manTrans = $manTrans;
        $this->servCollect = $servCollect;
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $output->writeln("<info>Start referral bonus collection.<info>");
        /* wrap all DB operations with DB transaction */
        $def = $this->manTrans->begin();
        $req = new ARequest();
        $this->servCollect->exec($req);
        $this->manTrans->commit($def);
        $output->writeln('<info>Command is completed.<info>');
    }
}