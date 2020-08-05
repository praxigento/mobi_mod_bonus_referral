<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2020
 */

namespace Test\Praxigento\BonusReferral\Service\Sale;


include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class Register
    extends \Praxigento\Core\Test\BaseCase\Manual
{

    public function test_execute()
    {
        $this->setAreaCode();
        /** @var \Magento\Sales\Api\OrderRepositoryInterface $repo */
        $repo = $this->manObj->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
        /** @var \Praxigento\BonusReferral\Service\Sale\Register $service */
        $service = $this->manObj->get(\Praxigento\BonusReferral\Service\Sale\Register::class);
        $req = new \Praxigento\BonusReferral\Service\Sale\Register\Request();
        $sale = $repo->get(3590);
        $req->setSaleOrder($sale);
        $resp = $service->exec($req);
        $this->assertTrue(true);
    }
}
