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
    /** @var  \Praxigento\Accounting\Api\Service\Account\Get */
    private $servAccount;
    /** @var \Praxigento\Accounting\Api\Service\Operation\Create */
    private $servOper;

    public function __construct(
        \Praxigento\Core\Api\App\Repo\Generic $daoGeneric,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnlCust,
        \Praxigento\Pv\Repo\Dao\Sale $daoSale,
        \Praxigento\BonusReferral\Repo\Dao\Registry $daoRefReg,
        \Praxigento\Accounting\Api\Service\Account\Get $servAccount,
        \Praxigento\Accounting\Api\Service\Operation\Create $servOper
    ) {
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
        $pvTotal = $sale->getTotal();
        /* get sale order data */
        list($saleCustId, $saleIncId) = $this->getSaleOrderData($saleId);
        list($isReferralSale, $parentId) = $this->getReferralSaleData($saleId);
        if (is_null($customerId)) {
            $customerId = $saleCustId;
        }
        if (!is_null($customerId) || $isReferralSale) {
            if ($isReferralSale) {
                $mlmId = $this->getMlmId($customerId);
                $note = "PV for referral sale #$saleIncId (cust.: $mlmId)";
                $customerId = $parentId;
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
            $reqGetAccSys->setIsSystem(TRUE);
            $respGetAccSys = $this->servAccount->exec($reqGetAccSys);
            /* create one operation with one transaction */
            $reqAddOper = new AnOperationRequest();
            $reqAddOper->setOperationTypeCode(Cfg::CODE_TYPE_OPER_PV_SALE_PAID);
            $reqAddOper->setOperationNote($note);
            $trans = [
                ATransaction::A_DEBIT_ACC_ID => $respGetAccSys->getId(),
                ATransaction::A_CREDIT_ACC_ID => $respGetAccCust->getId(),
                ATransaction::A_VALUE => $pvTotal,
                ATransaction::A_DATE_APPLIED => $dateApplied,
                ATransaction::A_NOTE => $note
            ];
            $reqAddOper->setTransactions([$trans]);
            $respAddOper = $this->servOper->exec($reqAddOper);
            $operId = $respAddOper->getOperationId();
            $result->setOperationId($operId);
            $result->markSucceed();
        } else {
            /* should we throw exception here or just log the error? */
        }
        return $result;
    }

    private function getMlmId($custId)
    {
        $entity = $this->daoDwnlCust->getById($custId);
        $result = $entity->getMlmId();
        return $result;
    }

    private function getReferralSaleData($saleId)
    {
        $isReferralSale = false;
        $parentId = null;
        $regSale = $this->daoRefReg->getById($saleId);
        if ($regSale) {
            /* branch for bank transfer payments */
            $isReferralSale = true;
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