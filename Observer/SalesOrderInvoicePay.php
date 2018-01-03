<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusReferral\Observer;

/**
 * Register referral bonus on invoice payments (check/money order).
 */
class SalesOrderInvoicePay
    implements \Magento\Framework\Event\ObserverInterface
{
    /* Names for the items in the event's data */
    const DATA_INVOICE = 'invoice';
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getData(self::DATA_INVOICE);
        $state = $invoice->getState();
        if ($state == \Magento\Sales\Model\Order\Invoice::STATE_PAID) {
            try {
                $this->logger->debug("Register referral bonus on invoice payment.");
            } catch (\Exception $e) {
                /* catch all exceptions and steal them */
                $msg = 'Error is occurred on referral bonus registration (invoice). Error: ' . $e->getMessage();
                $this->logger->error($msg);
            }
        }
    }

}