<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Sale;

use Magento\Sales\Model\Order as MSaleOrder;
use Praxigento\BonusReferral\Repo\Data\Registry as ERegistry;
use Praxigento\BonusReferral\Service\Sale\Calc\Request as ACalcReq;
use Praxigento\BonusReferral\Service\Sale\Calc\Response as ACalcResp;
use Praxigento\BonusReferral\Service\Sale\Register\Request as ARequest;
use Praxigento\BonusReferral\Service\Sale\Register\Response as AResponse;

/**
 * Internal service (module level) to register referral bonus.
 */
class Register
{
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnl;
    /** @var \Praxigento\BonusReferral\Repo\Dao\Registry */
    private $daoReg;
    /** @var \Magento\Directory\Model\CurrencyFactory */
    private $factCur;
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Warehouse\Api\Helper\Stock */
    private $hlpStock;
    /** @var \Praxigento\Core\Api\App\Logger\Main */
    private $logger;
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;
    /** @var \Praxigento\BonusReferral\Service\Sale\Calc */
    private $servCalc;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\CurrencyFactory $factCur,
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnl,
        \Praxigento\BonusReferral\Repo\Dao\Registry $daoReg,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        \Praxigento\Warehouse\Api\Helper\Stock $hlpStock,
        \Praxigento\BonusReferral\Service\Sale\Calc $servCalc
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->factCur = $factCur;
        $this->logger = $logger;
        $this->daoDwnl = $daoDwnl;
        $this->daoReg = $daoReg;
        $this->hlpConfig = $hlpConfig;
        $this->hlpStock = $hlpStock;
        $this->servCalc = $servCalc;
    }

    /**
     * Calculate bonus bounty & fee and convert to the base currency (for WALLET asset).
     *
     * @param \Magento\Sales\Model\Order $sale
     * @param int $uplineId
     * @return array
     * @throws \Exception
     */
    private function calcAmounts(
        \Magento\Sales\Model\Order $sale,
        int $uplineId
    ) {
        /* call to service to calculate bonus */
        $req = new ACalcReq();
        $req->setSaleOrder($sale);
        $req->setUplineId($uplineId);
        /** @var ACalcResp $resp */
        $resp = $this->servCalc->exec($req);
        $amount = $resp->getDelta();
        $fee = $resp->getFee();
        /* convert warehouse base currency into system base currency */
        $curBase = $this->scopeConfig->getValue(\Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE);
        $storeId = $sale->getStoreId();
        $curWrhs = $this->hlpStock->getStockCurrencyByStoreId($storeId);
        $curr = $this->factCur->create();
        $curr->load($curWrhs);
        $amountBase = $curr->convert($amount, $curBase);
        $amountBase = round($amountBase, 2);
        $feeBase = $curr->convert($fee, $curBase);
        $feeBase = round($feeBase, 2);
        $saleId = $sale->getId();
        $this->logger->debug("Sale $saleId ref. bonus conversion (amnt/fee): $amount/$fee => $amountBase/$feeBase.");
        return [$amountBase, $feeBase];
    }

    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);
        $sale = $request->getSaleOrder();
        $saleId = $sale->getId();
        $saleState = $sale->getState();
        $custId = $sale->getCustomerId();

        /** perform processing */
        $isEnabled = $this->hlpConfig->getBonusEnabled();
        if ($isEnabled) {
            $uplineId = $this->getUplineId($custId);
            list($amount, $fee) = $this->calcAmounts($sale, $uplineId);
            $state = ($saleState == MSaleOrder::STATE_PROCESSING)
                ? ERegistry::STATE_PENDING : ERegistry::STATE_REGISTERED;
            if ($amount > 0) {
                $this->registerBonus($saleId, $uplineId, $amount, $fee, $state);
                $this->logger->debug("Referral bonus for order #$saleId is registered (amount: $amount, fee: $fee).");
            }
        }
        /** compose result */
        $result = new AResponse();
        return $result;
    }

    /**
     * Get upline ID for current customer.
     *
     * @param int $custId
     * @return int
     */
    private function getUplineId($custId)
    {
        /** @var \Praxigento\Downline\Repo\Data\Customer $entity */
        $entity = $this->daoDwnl->getById($custId);
        $result = $entity->getParentId();
        return $result;
    }

    /**
     * Save new bonus entry in DB.
     *
     * @param int $saleId
     * @param int $custId
     * @param float $amount
     * @param float $fee
     * @param string $state
     */
    private function registerBonus($saleId, $custId, $amount, $fee, $state)
    {
        $entity = new ERegistry();
        $entity->setSaleRef($saleId);
        $entity->setUplineRef($custId);
        $entity->setAmountTotal($amount);
        $entity->setAmountFee($fee);
        $entity->setState($state);
        $this->daoReg->create($entity);
    }
}