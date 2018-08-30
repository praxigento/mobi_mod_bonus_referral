<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Praxigento\BonusReferral\Plugin\Praxigento\Pv\Observer;

use Praxigento\BonusReferral\Service\Sale\Register\Request as ARequest;

class SalesModelServiceQuoteSubmitSuccess
{
    /* Names for the items in the event's data */
    const DATA_ORDER = 'order';

    /** @var \Praxigento\Core\Api\App\Logger\Main */
    private $logger;
    /** @var \Praxigento\BonusReferral\Service\Sale\Register */
    private $servReg;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\BonusReferral\Service\Sale\Register $servReg
    ) {
        $this->logger = $logger;
        $this->servReg = $servReg;
    }

    public function beforeExecute(
        \Praxigento\Pv\Observer\SalesModelServiceQuoteSubmitSuccess $subject,
        \Magento\Framework\Event\Observer $observer
    ) {
        $sale = $observer->getData(self::DATA_ORDER);
        try {
            $this->logger->info("Register referral bonus on checkout.");
            $req = new ARequest();
            $req->setSaleOrder($sale);
            $this->servReg->exec($req);
        } catch (\Throwable $e) {
            /* catch all exceptions and stealth them */
            $msg = 'Error is occurred on referral bonus registration. Error: ' . $e->getMessage();
            $this->logger->error($msg);
        }
        /* return nothing to use original input arguments */
    }
}