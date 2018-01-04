<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Test\Service\Sale;

include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class Calc
    extends \Praxigento\Core\Test\BaseCase\Manual
{

    public function test_execute()
    {
        $this->setAreaCode();
        /** @var \Praxigento\BonusReferral\Service\Sale\Calc $obj */
        $obj = $this->manObj->get(\Praxigento\BonusReferral\Service\Sale\Calc::class);
        $req = new \Praxigento\BonusReferral\Service\Sale\Calc\Request();
        $req->setSaleId(48);
        $req->setUplineId(8878);
        $resp = $obj->exec($req);
        $this->assertInstanceOf(\Praxigento\BonusReferral\Service\Sale\Calc\Response::class, $resp);
    }
}