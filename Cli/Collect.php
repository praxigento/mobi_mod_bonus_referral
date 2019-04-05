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
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect */
    private $servCollect;

    public function __construct(
        \Praxigento\BonusReferral\Service\Bonus\Collect $servCollect
    ) {
        parent::__construct(
            'prxgt:bonus:referral:collect',
            'Collect and pay referral bonus after delay.'
        );
        $this->servCollect = $servCollect;
    }

    protected function process(\Symfony\Component\Console\Input\InputInterface $input)
    {
        $req = new ARequest();
        $this->servCollect->exec($req);
    }
}