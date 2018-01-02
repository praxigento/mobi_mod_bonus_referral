<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Sale;

use Praxigento\BonusReferral\Service\Sale\Calc\Request as ARequest;
use Praxigento\BonusReferral\Service\Sale\Calc\Response as AResponse;

class Calc
{
    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    public $repoQuote;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $repoQuote
    ) {
        $this->repoQuote = $repoQuote;
    }

    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);
        $quoteId = $request->getQuoteId();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->repoQuote->get($quoteId);

        /** perform processing */
//        $quote->setId(0); // prevent quote save on some event
//        $quote->setBillingAddress(null);
//        $quote->setShippingAddress(null);
//        $quote->setPayment(null);
        $quote->collectTotals();
        $grand = $quote->getBaseGrandTotal();
        $subtotal = $quote->getBaseSubtotal();
        $quote->setCustomerId(8878);
        $quote->collectTotals();
        $subtotalUp = $quote->getBaseSubtotalWithDiscount();

        /** compose result */

        $result = new AResponse();
        return $result;
    }
}