<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Praxigento\BonusReferral\Service\Sale\Account;

use Praxigento\Accounting\Api\Service\Account\Get\Request as AnAccountGetRequest;
use Praxigento\Accounting\Api\Service\Operation\Create\Request as AnOperationRequest;
use Praxigento\Accounting\Repo\Data\Transaction as ATransaction;
use Praxigento\BonusReferral\Config as Cfg;
use Praxigento\Pv\Api\Service\Sale\Account\Pv\Request as ARequest;
use Praxigento\Pv\Api\Service\Sale\Account\Pv\Response as AResponse;

/**
 * PV is paid to the sponsor's of the referral customer for referral sales.
 */
class Pv
    implements \Praxigento\Pv\Api\Service\Sale\Account\Pv
{
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnlCust;
    /** @var \Praxigento\Core\Api\App\Repo\Generic */
    private $daoGeneric;
    /** @var \Praxigento\BonusReferral\Repo\Dao\Registry */
    private $daoRefReg;
    /** @var  \Praxigento\Pv\Repo\Dao\Sale */
    private $daoSale;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var  \Praxigento\Accounting\Api\Service\Account\Get */
    private $servAccount;
    /** @var \Praxigento\Accounting\Api\Service\Operation\Create */
    private $servOper;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\Core\Api\App\Repo\Generic $daoGeneric,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnlCust,
        \Praxigento\Pv\Repo\Dao\Sale $daoSale,
        \Praxigento\BonusReferral\Repo\Dao\Registry $daoRefReg,
        \Praxigento\Accounting\Api\Service\Account\Get $servAccount,
        \Praxigento\Accounting\Api\Service\Operation\Create $servOper
    ) {
        $this->logger = $logger;
        $this->daoGeneric = $daoGeneric;
        $this->daoDwnlCust = $daoDwnlCust;
        $this->daoSale = $daoSale;
        $this->daoRefReg = $daoRefReg;
        $this->servAccount = $servAccount;
        $this->servOper = $servOper;
    }

    /**
     * @param ARequest $request
     * @return AResponse
     */
    public function exec($request)
    {
        $result = new AResponse();
        $saleId = $request->getSaleOrderId();
        $customerId = $request->getCustomerId();
        $dateApplied = $request->getDateApplied();
        $sale = $this->daoSale->getById($saleId);
        $tranId = $sale->getTransRef();
        if (is_null($tranId)) {
            $pvTotal = $sale->getTotal();
            /* get sale order data */
            list($saleCustId, $saleIncId) = $this->getSaleOrderData($saleId);
            /** @var int $beneficiaryId ID of the customer who should get PV for the sale (upline or customer itself) */
            list($isReferralSale, $beneficiaryId) = $this->getReferralSaleData($saleId);
            if (is_null($customerId)) {
                $customerId = $saleCustId;
            }
            if (!is_null($customerId) || $isReferralSale) {
                if ($isReferralSale) {
                    $referralId = $this->getReferralId($customerId);
                    $note = "PV for referral order #$saleIncId ($referralId)";
                    $customerId = $beneficiaryId;
                } else {
                    $note = "PV for sale #$saleIncId";
                }
                /* get PV account data for customer */
                $reqGetAccCust = new AnAccountGetRequest();
                $reqGetAccCust->setCustomerId($customerId);
                $reqGetAccCust->setAssetTypeCode(Cfg::CODE_TYPE_ASSET_PV);
                $respGetAccCust = $this->servAccount->exec($reqGetAccCust);
                /* get PV account data for system */
                $reqGetAccSys = new AnAccountGetRequest();
                $reqGetAccSys->setAssetTypeCode(Cfg::CODE_TYPE_ASSET_PV);
                $reqGetAccSys->setIsSystem(true);
                $respGetAccSys = $this->servAccount->exec($reqGetAccSys);
                /* create one operation with one transaction */
                $reqAddOper = new AnOperationRequest();
                $reqAddOper->setOperationTypeCode(Cfg::CODE_TYPE_OPER_PV_SALE_PAID);
                $reqAddOper->setOperationNote($note);
                $tran = new ATransaction();
                $tran->setDebitAccId($respGetAccSys->getId());
                $tran->setCreditAccId($respGetAccCust->getId());
                $tran->setValue($pvTotal);
                $tran->setDateApplied($dateApplied);
                $tran->setNote($note);
                $trans = [$tran];
                $reqAddOper->setTransactions($trans);
                $respAddOper = $this->servOper->exec($reqAddOper);
                $operId = $respAddOper->getOperationId();
                $tranIds = $respAddOper->getTransactionsIds();
                $tranId = reset($tranIds);
                $sale->setTransRef($tranId);
                $this->daoSale->updateById($saleId, $sale);
                $result->setOperationId($operId);
                $result->markSucceed();
            } else {
                /* should we throw exception here or just log the error? */
            }
        } else {
            $this->logger->error("PV accounting error: there is transaction #$tranId for sale #$saleId.");
        }
        return $result;
    }

    private function getReferralId($custId)
    {
        // get MLM ID
        $entity = $this->daoDwnlCust->getById($custId);
        $mlmId = $entity->getMlmId();
        // get customer name
        /* get referral customer ID */
        $pkey = [Cfg::E_COMMON_A_ENTITY_ID => $custId];
        $cols = [
            Cfg::E_CUSTOMER_A_FIRSTNAME,
            Cfg::E_CUSTOMER_A_LASTNAME
        ];
        $entity = $this->daoGeneric->getEntityByPk(Cfg::ENTITY_MAGE_CUSTOMER, $pkey, $cols);
        $nameFirst = $entity[Cfg::E_CUSTOMER_A_FIRSTNAME];
        $nameLast = $entity[Cfg::E_CUSTOMER_A_LASTNAME];
        // compose result
        $result = "by $nameFirst $nameLast, #$mlmId";
        return $result;
    }

    private function getReferralSaleData($saleId)
    {
        $isReferralSale = false;
        $parentId = null;
        $regSale = $this->daoRefReg->getById($saleId);
        if ($regSale) {
            $isReferralSale = true;
            /*
             *  uplineRef = customerRef if referral has more than 100 PV in the sale
             *  (see \Praxigento\Santegra\Helper\BonReferral\Register::getBeneficiaryId)
             */
            $parentId = $regSale->getUplineRef();
        }
        return [$isReferralSale, $parentId];
    }

    /**
     * Get significant attributes of the sale order.
     *
     * @param int $saleId
     * @return array [$custId, $incId]
     */
    private function getSaleOrderData($saleId)
    {
        /* get referral customer ID */
        $pkey = [Cfg::E_COMMON_A_ENTITY_ID => $saleId];
        $cols = [
            Cfg::E_SALE_ORDER_A_CUSTOMER_ID,
            Cfg::E_SALE_ORDER_A_INCREMENT_ID
        ];
        $entity = $this->daoGeneric->getEntityByPk(Cfg::ENTITY_MAGE_SALES_ORDER, $pkey, $cols);
        $custId = $entity[Cfg::E_SALE_ORDER_A_CUSTOMER_ID];
        $incId = $entity[Cfg::E_SALE_ORDER_A_INCREMENT_ID];
        return [$custId, $incId];
    }
}