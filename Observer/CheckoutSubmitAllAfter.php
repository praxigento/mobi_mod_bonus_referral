<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Observer;

use Praxigento\BonusReferral\Service\Sale\Register\Request as ARequest;

/**
 * Register referral bonus for credit cards payments.
 * (spacer between Magneto code & MOBI services)
 */
class CheckoutSubmitAllAfter
    implements \Magento\Framework\Event\ObserverInterface
{
    /* Names for the items in the event's data */
    const DATA_ORDER = 'order';

    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var \Praxigento\BonusReferral\Service\Sale\Register */
    private $servReg;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Praxigento\BonusReferral\Service\Sale\Register $servReg
    ) {
        $this->logger = $logger;
        $this->servReg = $servReg;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $sale */
        $sale = $observer->getData(self::DATA_ORDER);
        $state = $sale->getState();
        if ($state == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            try {
                $this->logger->debug("Register referral bonus on checkout.");
                $req = new ARequest();
                $req->setSaleOrder($sale);
                $this->servReg->exec($req);
            } catch (\Throwable $e) {
                /* catch all exceptions and steal them */
                $msg = 'Error is occurred on referral bonus registration (checkout). Error: ' . $e->getMessage();
                $this->logger->error($msg);
            }
        }
    }

}