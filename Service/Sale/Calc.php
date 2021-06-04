<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Sale;

use Praxigento\BonusReferral\Service\Sale\Calc\Request as ARequest;
use Praxigento\BonusReferral\Service\Sale\Calc\Response as AResponse;
use Praxigento\Warehouse\Api\Data\Catalog\Product as AWrhsProd;

/**
 * Internal service (module level) to calculate referral bonus amount & processing fee.
 */
class Calc
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $daoCust;
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $daoProd;
    /** @var \Magento\Quote\Model\QuoteFactory */
    private $factQuote;
    /** @var \Magento\Quote\Model\Quote\AddressFactory */
    private $factQuoteAddr;
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Core\Api\Helper\Format */
    private $hlpFormat;
    /** @var \Praxigento\Warehouse\Helper\PriceLoader */
    private $hlpPriceLoader;
    /** @var \Praxigento\Core\Api\App\Logger\Main */
    private $logger;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Magento\Quote\Model\QuoteFactory $factQuote,
        \Magento\Quote\Model\Quote\AddressFactory $factQuoteAddr,
        \Magento\Catalog\Api\ProductRepositoryInterface $daoProd,
        \Magento\Customer\Api\CustomerRepositoryInterface $daoCust,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        \Praxigento\Core\Api\Helper\Format $hlpFormat,
        \Praxigento\Warehouse\Helper\PriceLoader $hlpPriceLoader
    ) {
        $this->logger = $logger;
        $this->factQuote = $factQuote;
        $this->factQuoteAddr = $factQuoteAddr;
        $this->daoProd = $daoProd;
        $this->daoCust = $daoCust;
        $this->hlpConfig = $hlpConfig;
        $this->hlpFormat = $hlpFormat;
        $this->hlpPriceLoader = $hlpPriceLoader;
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
        $result = $this->hlpFormat->toNumber($result);
        return $result;
    }

    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);
        $result = new AResponse();
        $bnfId = $request->getBeneficiaryId();
        $bnfGroupId = $request->getBeneficiaryGroupId();
        /** @var \Magento\Sales\Model\Order $sale */
        $sale = $request->getSaleOrder();

        /** perform processing */
        try {
            $beneficiary = $this->daoCust->getById($bnfId);
            $bnfGroupId = $bnfGroupId ?? $beneficiary->getGroupId();
            $storeId = $sale->getStoreId();
            $saleId = $sale->getId();
            /* init quote itself */
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->factQuote->create();
            $quote->setStoreId($storeId);
            $quote->setCustomer($beneficiary);
            $quote->setCustomerIsGuest(0);
            /* init quote items */
            $items = $sale->getItemsCollection();
            foreach ($items as $item) {
                /* load product */
                $prodId = $item->getProductId();
                $product = $this->daoProd->getById($prodId, false, $storeId, true);
                /* load group price for upline */
                list($priceWrhs, $priceWrhsGroup) = $this->hlpPriceLoader->load($prodId, $storeId, $bnfGroupId);
                $product->setData(AWrhsProd::A_PRICE_WRHS, $priceWrhs);
                $product->setData(AWrhsProd::A_PRICE_WRHS_GROUP, $priceWrhsGroup);
                /* add product to quote */
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
            $this->logger->info("Referral bonus calc for sale $saleId (ref - distr = delta; fee): $baseAmntUp - $baseAmntCust = $delta; $fee");
            /** compose result */
            if ($delta) $result->setDelta($delta);
            if ($fee) $result->setFee($fee);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

}
