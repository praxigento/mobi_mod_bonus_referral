<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Test\Service\Bonus\Collect\A\Repo\Query;

use Praxigento\BonusReferral\Service\Bonus\Collect\A\Repo\Query\GetRegistered as QBGetRegs;

include_once(__DIR__ . '/../../../../../../phpunit_bootstrap.php');

class SalesOrderInvoicePay
    extends \Praxigento\Core\Test\BaseCase\Manual
{

    public function test_execute()
    {
        $this->setAreaCode();
        /** @var \Praxigento\BonusReferral\Service\Bonus\Collect\A\Repo\Query\GetRegistered $builder */
        $builder = $this->manObj->get(\Praxigento\BonusReferral\Service\Bonus\Collect\A\Repo\Query\GetRegistered::class);
        $query = $builder->build();
        $conn = $query->getConnection();
        $bind = [
            QBGetRegs::BND_DATE_PAID => '2019/12/31'];
        $result = $conn->fetchAll($query, $bind);
        return $result;
    }
}