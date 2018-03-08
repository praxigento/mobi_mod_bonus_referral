<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Sale;

use Praxigento\BonusReferral\Service\Sale\Calc\Repo\Query\Product\Prices as QBPrices;
use Praxigento\BonusReferral\Service\Sale\Calc\Request as ARequest;
use Praxigento\BonusReferral\Service\Sale\Calc\Response as AResponse;
use Praxigento\Warehouse\Plugin\Catalog\Model\Product\Type\Price as APricePlugin;

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
    /** @var \Praxigento\Core\App\Api\Logger\Main */
    private $logger;
    /** @var \Praxigento\BonusReferral\Service\Sale\Calc\Repo\Query\Product\Prices */
    private $qbPrices;
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $repoCust;
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $repoProd;
    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $repoSaleOrder;

    public function __construct(
        \Praxigento\Core\App\Api\Logger\Main $logger,
        \Magento\Quote\Model\QuoteFactory $factQuote,
        \Magento\Quote\Model\Quote\AddressFactory $factQuoteAddr,
        \Magento\Catalog\Api\ProductRepositoryInterface $repoProd,
        \Magento\Customer\Api\CustomerRepositoryInterface $repoCust,
        \Magento\Sales\Api\OrderRepositoryInterface $repoSaleOrder,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        QBPrices $qbPrices
    ) {
        $this->logger = $logger;
        $this->factQuote = $factQuote;
        $this->factQuoteAddr = $factQuoteAddr;
        $this->repoProd = $repoProd;
        $this->repoCust = $repoCust;
        $this->repoSaleOrder = $repoSaleOrder;
        $this->hlpConfig = $hlpConfig;
        $this->qbPrices = $qbPrices;
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
            $customer = $this->repoCust->getById($custId);
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
                $product = $this->repoProd->getById($prodId, false, $storeId, true);
                /* add warehouse prices */
                list($priceWrhs, $priceGroup) = $this->getPrices($storeId, $prodId, $custGroupId);
                $product->setData(APricePlugin::A_PRICE_WRHS, $priceWrhs);
                $product->setData(APricePlugin::A_PRICE_WRHS_GROUP, $priceGroup);
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

    private function getPrices($storeId, $prodId, $groupId)
    {
        $priceWrhs = $priceGroup = null;

        $query = $this->qbPrices->build();
        $conn = $query->getConnection();
        $bind = [
            QBPrices::BND_STOCK_ID => $storeId,
            QBPrices::BND_PROD_ID => $prodId,
            QBPrices::BND_GROUP_ID => $groupId
        ];
        $row = $conn->fetchRow($query, $bind);
        if ($row) {
            if ($row[QBPrices::A_PRICE_WRHS]) $priceWrhs = $row[QBPrices::A_PRICE_WRHS];
            if ($row[QBPrices::A_PRICE_GROUP]) $priceGroup = $row[QBPrices::A_PRICE_GROUP];
        }

        $result = [$priceWrhs, $priceGroup];
        return $result;
    }
}