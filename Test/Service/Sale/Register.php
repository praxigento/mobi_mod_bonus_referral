<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Test\Service\Sale;

include_once(__DIR__ . '/../../phpunit_bootstrap.php');

class Register
    extends \Praxigento\Core\Test\BaseCase\Manual
{

    public function test_execute()
    {
        $this->setAreaCode();
        /** @var \Praxigento\BonusReferral\Service\Sale\Register $obj */
        $obj = $this->manObj->get(\Praxigento\BonusReferral\Service\Sale\Register::class);
        $req = new \Praxigento\BonusReferral\Service\Sale\Register\Request();
        $resp = $obj->exec($req);
        $this->assertInstanceOf(\Praxigento\BonusReferral\Service\Sale\Register\Response::class, $resp);
    }
}