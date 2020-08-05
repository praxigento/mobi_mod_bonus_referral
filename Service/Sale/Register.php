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
    /** @var \Praxigento\BonusReferral\Repo\Dao\Registry */
    private $daoReg;
    /** @var \Magento\Directory\Model\CurrencyFactory */
    private $factCur;
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Downline\Api\Helper\Config */
    private $hlpDwnlCfg;
    /** @var \Praxigento\BonusReferral\Api\Helper\Register */
    private $hlpRegister;
    /** @var \Praxigento\Warehouse\Api\Helper\Stock */
    private $hlpStock;
    /** @var \Praxigento\Core\Api\App\Logger\Main */
    private $logger;
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $repoCust;
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;
    /** @var \Praxigento\BonusReferral\Service\Sale\Calc */
    private $servCalc;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\CurrencyFactory $factCur,
        \Magento\Customer\Api\CustomerRepositoryInterface $repoCust,
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\BonusReferral\Repo\Dao\Registry $daoReg,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        \Praxigento\Downline\Api\Helper\Config $hlpDwnlCfg,
        \Praxigento\BonusReferral\Api\Helper\Register $hlpRegister,
        \Praxigento\Warehouse\Api\Helper\Stock $hlpStock,
        \Praxigento\BonusReferral\Service\Sale\Calc $servCalc
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->factCur = $factCur;
        $this->repoCust = $repoCust;
        $this->logger = $logger;
        $this->daoReg = $daoReg;
        $this->hlpConfig = $hlpConfig;
        $this->hlpDwnlCfg = $hlpDwnlCfg;
        $this->hlpRegister = $hlpRegister;
        $this->hlpStock = $hlpStock;
        $this->servCalc = $servCalc;
    }

    /**
     * Calculate bonus bounty & fee and convert to the base currency (for WALLET asset).
     *
     * @param \Magento\Sales\Model\Order $sale
     * @param int $beneficiaryId
     * @return array
     * @throws \Exception
     */
    private function calcAmounts(
        \Magento\Sales\Model\Order $sale,
        int $beneficiaryId,
        int $bnfGroupId
    ) {
        /* call to service to calculate bonus */
        $req = new ACalcReq();
        $req->setSaleOrder($sale);
        $req->setBeneficiaryId($beneficiaryId);
        $req->setBeneficiaryGroupId($bnfGroupId);
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
        $this->logger->info("Sale $saleId ref. bonus conversion (amnt/fee): $amount/$fee => $amountBase/$feeBase.");
        return [$amountBase, $feeBase];
    }

    /**
     * Change group for customer if customer is not distributor but should get referral bonus.
     *
     * @param $custId
     * @param $groupId
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    private function changeGroupToDistr($custId, $groupId)
    {
        $customer = $this->repoCust->getById($custId);
        $customer->setGroupId($groupId);
        $this->repoCust->save($customer);
    }

    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);
        $sale = $request->getSaleOrder();
        $saleId = $sale->getId();
        $saleState = $sale->getState();

        /** perform processing */
        $isEnabled = $this->hlpConfig->getBonusEnabled();
        if ($isEnabled) {
            $distrGroups = $this->hlpDwnlCfg->getDowngradeGroupsDistrs();
            $groupId = $sale->getCustomerGroupId();
            $custId = $sale->getCustomerId();
            $isDistr = in_array($groupId, $distrGroups);
            if (!$isDistr) {
                [$beneficiaryId, $bnfGroupId] = $this->hlpRegister->getBeneficiaryId($sale);
                if ($custId == $beneficiaryId) {
                    /* customer is beneficiary of referral bonus for own sale but is not distr */
                    /* we should change group for customer */
                    $this->changeGroupToDistr($custId, $bnfGroupId);
                }
                list($amount, $fee) = $this->calcAmounts($sale, $beneficiaryId, $bnfGroupId);
                $state = ($saleState == MSaleOrder::STATE_PROCESSING)
                    ? ERegistry::STATE_PENDING : ERegistry::STATE_REGISTERED;
                if ($amount > 0) {
                    $this->registerBonus($saleId, $beneficiaryId, $amount, $fee, $state);
                    $this->logger->info("Referral bonus for order #$saleId is registered (amount: $amount, fee: $fee).");
                }
            }
        }
        /** compose result */
        $result = new AResponse();
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
     * @throws \Exception
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
