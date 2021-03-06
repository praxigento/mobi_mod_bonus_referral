<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Observer;

use Praxigento\BonusReferral\Repo\Data\Registry as EReg;

/**
 * Change state for registered referral bonus on invoice payments (check/money order).
 */
class SalesOrderInvoicePay
    implements \Magento\Framework\Event\ObserverInterface
{
    /* Names for the items in the event's data */
    const DATA_INVOICE = 'invoice';
    /** @var \Praxigento\Core\Api\App\Logger\Main */
    private $logger;
    /** @var \Praxigento\BonusReferral\Repo\Dao\Registry */
    private $daoReg;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\BonusReferral\Repo\Dao\Registry $daoReg
    ) {
        $this->logger = $logger;
        $this->daoReg = $daoReg;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getData(self::DATA_INVOICE);
        $invState = $invoice->getState();
        if ($invState == \Magento\Sales\Model\Order\Invoice::STATE_PAID) {
            try {
                $sale = $invoice->getOrder();
                $saleId = $sale->getId();
                if ($saleId) {
                    $registry = $this->daoReg->getById($saleId);
                    if ($registry) {
                        $regState = $registry->getState();
                        if ($regState == EReg::STATE_REGISTERED) {
                            $registry->setState(EReg::STATE_PENDING);
                            $pk = [EReg::A_SALE_REF => $saleId];
                            $this->daoReg->updateById($pk, $registry);
                            $msg = "Sale order #$saleId is paid. "
                                . EReg::STATE_PENDING . " state is set for related referral bonus.";
                            $this->logger->info($msg);
                        }
                    }
                }
            } catch (\Throwable $e) {
                /* catch all exceptions and steal them */
                $msg = 'Error is occurred on referral bonus state change. Error: ' . $e->getMessage();
                $this->logger->error($msg);
            }
        }
    }

}