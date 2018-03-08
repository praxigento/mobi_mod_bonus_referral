<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Observer;

use Praxigento\BonusReferral\Repo\Entity\Data\Registry as EReg;

/**
 * Register referral bonus on invoice payments (check/money order).
 * (spacer between Magneto code & MOBI services)
 */
class SalesOrderInvoicePay
    implements \Magento\Framework\Event\ObserverInterface
{
    /* Names for the items in the event's data */
    const DATA_INVOICE = 'invoice';
    /** @var \Praxigento\Core\App\Api\Logger\Main */
    private $logger;
    /** @var \Praxigento\BonusReferral\Repo\Entity\Registry */
    private $repoReg;

    public function __construct(
        \Praxigento\Core\App\Api\Logger\Main $logger,
        \Praxigento\BonusReferral\Repo\Entity\Registry $repoReg
    ) {
        $this->logger = $logger;
        $this->repoReg = $repoReg;
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
                $registry = $this->repoReg->getById($saleId);
                if ($registry) {
                    $regState = $registry->getState();
                    if ($regState == EReg::STATE_REGISTERED) {
                        $registry->setState(EReg::STATE_PENDING);
                        $pk = [EReg::ATTR_SALE_REF => $saleId];
                        $this->repoReg->updateById($pk, $registry);
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