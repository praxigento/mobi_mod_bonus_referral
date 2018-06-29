<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Test\Service\Sale\Calc;

use Praxigento\BonusReferral\Observer\CheckoutSubmitAllAfter as AObserver;

include_once(__DIR__ . '/../phpunit_bootstrap.php');

class CheckoutSubmitAllAfter
    extends \Praxigento\Core\Test\BaseCase\Manual
{

    public function test_execute()
    {
        $this->setAreaCode();
        /** @var \Magento\Sales\Api\OrderRepositoryInterface $daoOrder */
        $daoOrder = $this->manObj->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $sale = $daoOrder->get(1);
        /** @var \Magento\Framework\Event\Observer $event */
        $event = $this->manObj->get(\Magento\Framework\Event\Observer::class);
        $event->setData(AObserver::DATA_ORDER, $sale);
        /** @var \Praxigento\BonusReferral\Observer\CheckoutSubmitAllAfter $obj */
        $obj = $this->manObj->get(\Praxigento\BonusReferral\Observer\CheckoutSubmitAllAfter::class);
        $obj->execute($event);
    }
}