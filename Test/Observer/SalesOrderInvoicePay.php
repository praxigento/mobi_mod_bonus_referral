<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Test\Service\Sale\Calc;

use Praxigento\BonusReferral\Observer\SalesOrderInvoicePay as AObserver;

include_once(__DIR__ . '/../phpunit_bootstrap.php');

class SalesOrderInvoicePay
    extends \Praxigento\Core\Test\BaseCase\Manual
{

    public function test_execute()
    {
        $this->setAreaCode();
        /** @var \Magento\Sales\Api\InvoiceRepositoryInterface $daoInvoice */
        $daoInvoice = $this->manObj->get(\Magento\Sales\Api\InvoiceRepositoryInterface::class);
        $sale = $daoInvoice->get(2);
        /** @var \Magento\Framework\Event\Observer $event */
        $event = $this->manObj->get(\Magento\Framework\Event\Observer::class);
        $event->setData(AObserver::DATA_INVOICE, $sale);
        /** @var \Praxigento\BonusReferral\Observer\SalesOrderInvoicePay $obj */
        $obj = $this->manObj->get(\Praxigento\BonusReferral\Observer\SalesOrderInvoicePay::class);
        $obj->execute($event);
    }
}