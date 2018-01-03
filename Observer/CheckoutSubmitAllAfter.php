<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Observer;

/**
 * Register referral bonus for credit cards payments.
 */
class CheckoutSubmitAllAfter
    implements \Magento\Framework\Event\ObserverInterface
{
    /* Names for the items in the event's data */
    const DATA_ORDER = 'order';

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData(self::DATA_ORDER);
        $state = $order->getState();
        if ($state == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            try {
                $this->logger->debug("Register referral bonus on checkout.");
            } catch (\Exception $e) {
                /* catch all exceptions and steal them */
                $msg = 'Error is occurred on referral bonus registration (checkout). Error: ' . $e->getMessage();
                $this->logger->error($msg);
            }
        }
    }

}