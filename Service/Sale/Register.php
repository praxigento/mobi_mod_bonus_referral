<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Sale;

use Magento\Sales\Model\Order as MSaleOrder;
use Praxigento\BonusReferral\Repo\Entity\Data\Registry as ERegistry;
use Praxigento\BonusReferral\Service\Sale\Calc\Request as ACalcReq;
use Praxigento\BonusReferral\Service\Sale\Calc\Response as ACalcResp;
use Praxigento\BonusReferral\Service\Sale\Register\Request as ARequest;
use Praxigento\BonusReferral\Service\Sale\Register\Response as AResponse;

/**
 * Internal service (module level) to register referral bonus.
 */
class Register
{
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Core\App\Api\Logger\Main */
    private $logger;
    /** @var \Praxigento\Downline\Repo\Entity\Customer */
    private $repoDwnl;
    /** @var \Praxigento\BonusReferral\Repo\Entity\Registry */
    private $repoReg;
    /** @var \Praxigento\BonusReferral\Service\Sale\Calc */
    private $servCalc;

    public function __construct(
        \Praxigento\Core\App\Api\Logger\Main $logger,
        \Praxigento\Downline\Repo\Entity\Customer $repoDwnl,
        \Praxigento\BonusReferral\Repo\Entity\Registry $repoReg,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        \Praxigento\BonusReferral\Service\Sale\Calc $servCalc
    ) {
        $this->logger = $logger;
        $this->repoDwnl = $repoDwnl;
        $this->repoReg = $repoReg;
        $this->hlpConfig = $hlpConfig;
        $this->servCalc = $servCalc;
    }

    private function calcAmounts(
        \Magento\Sales\Model\Order $sale,
        int $uplineId
    ) {
        $req = new ACalcReq();
        $req->setSaleOrder($sale);
        $req->setUplineId($uplineId);
        /** @var ACalcResp $resp */
        $resp = $this->servCalc->exec($req);
        $amount = $resp->getDelta();
        $fee = $resp->getFee();
        return [$amount, $fee];
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
        /** @var \Praxigento\Downline\Repo\Entity\Data\Customer $entity */
        $entity = $this->repoDwnl->getById($custId);
        $result = $entity->getParentId();
        return $result;
    }

    /**
     * Save new bonus entry in DB.
     *
     * @param int $saleId
     * @param int $custId
     * @param float $amount
     * @param floet $fee
     */
    private function registerBonus($saleId, $custId, $amount, $fee, $state)
    {
        $entity = new ERegistry();
        $entity->setSaleRef($saleId);
        $entity->setUplineRef($custId);
        $entity->setAmountTotal($amount);
        $entity->setAmountFee($fee);
        $entity->setState($state);
        $this->repoReg->create($entity);
    }
}