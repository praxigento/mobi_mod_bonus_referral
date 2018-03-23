<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Sale;

use Praxigento\BonusReferral\Service\Sale\Calc\Request as ARequest;
use Praxigento\BonusReferral\Service\Sale\Calc\Response as AResponse;

/**
 * Internal service (module level) to calculate referral bonus amount & processing fee.
 */
class Calc
{
    /** @var \Magento\Quote\Model\QuoteFactory */
    private $factQuote;
    /** @var \Magento\Quote\Model\Quote\AddressFactory */
    private $factQuoteAddr;
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Core\Api\App\Logger\Main */
    private $logger;
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $daoCust;
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $daoProd;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Magento\Quote\Model\QuoteFactory $factQuote,
        \Magento\Quote\Model\Quote\AddressFactory $factQuoteAddr,
        \Magento\Catalog\Api\ProductRepositoryInterface $daoProd,
        \Magento\Customer\Api\CustomerRepositoryInterface $daoCust,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig
    ) {
        $this->logger = $logger;
        $this->factQuote = $factQuote;
        $this->factQuoteAddr = $factQuoteAddr;
        $this->daoProd = $daoProd;
        $this->daoCust = $daoCust;
        $this->hlpConfig = $hlpConfig;
    }

    private function calculateFee($amount)
    {
        $fixed = $this->hlpConfig->getBonusFeeFixed();
        $percent = $this->hlpConfig->getBonusFeePercent();
        $min = $this->hlpConfig->getBonusFeeMin();
        $max = $this->hlpConfig->getBonusFeeMax();
        $result = $fixed + $amount * $percent;
        $result = ($result < $min) ? $min : $result;
        $result = ($result > $max) ? $max : $result;
        $result = number_format($result, 2);
        return $result;
    }

    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);
        $custId = $request->getUplineId();
        /** @var \Magento\Sales\Model\Order $sale */
        $sale = $request->getSaleOrder();

        /** perform processing */
        try {
            $customer = $this->daoCust->getById($custId);
            $custGroupId = $customer->getGroupId();
            $storeId = $sale->getStoreId();
            /* init quote itself */
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->factQuote->create();
            $quote->setStoreId($storeId);
            $quote->setCustomer($customer);
            $quote->setCustomerIsGuest(0);
            /* init quote items */
            $items = $sale->getItemsCollection();
            foreach ($items as $item) {
                $prodId = $item->getProductId();
                $product = $this->daoProd->getById($prodId, false, $storeId, true);
                $qty = $item->getQtyOrdered();
                $quote->addProduct($product, $qty);
            }
            /* init shipping address */
            $addrSaleShip = $sale->getShippingAddress();
            $data = $addrSaleShip->getData();
            $addrQuoteShip = $this->factQuoteAddr->create($data);
            $quote->addShippingAddress($addrQuoteShip);

            /* collect totals */
            $quote->collectTotals();
            $baseAmntUp = $quote->getBaseSubtotalWithDiscount();
            $baseAmntCust = $sale->getBaseSubtotal() - $sale->getBaseDiscountAmount() + $sale->getBaseShippingDiscountAmount();
            $delta = $baseAmntCust - $baseAmntUp;
            $fee = $this->calculateFee($delta);

        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
        /** compose result */
        $result = new AResponse();
        if ($delta) $result->setDelta($delta);
        if ($fee) $result->setFee($fee);
        return $result;
    }

}