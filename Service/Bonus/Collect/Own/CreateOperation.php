<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Bonus\Collect\Own;

use Praxigento\Accounting\Api\Service\Operation\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Response as ARespOper;
use Praxigento\Accounting\Repo\Entity\Data\Transaction as ETrans;
use Praxigento\BonusReferral\Config as Cfg;

class CreateOperation
{
    /** @var \Praxigento\Accounting\Repo\Entity\Account */
    private $repoAcc;
    /** @var \Praxigento\Accounting\Repo\Entity\Type\Asset */
    private $repoAssetType;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    public function __construct(
        \Praxigento\Accounting\Repo\Entity\Account $repoAcc,
        \Praxigento\Accounting\Repo\Entity\Type\Asset $repoAssetType,
        \Praxigento\Accounting\Api\Service\Operation $servOper
    ) {
        $this->repoAcc = $repoAcc;
        $this->repoAssetType = $repoAssetType;
        $this->servOper = $servOper;
    }

    /**
     * @param int $saleId
     * @param int $custId
     * @param float $amount bounty or fee value (positive)
     * @param bool $isBounty 'true' - pay $amount to customer, 'false' - pay fee from customer account
     * @return int operation ID
     * @throws \Exception
     */
    public function exec($saleId, $custId, $amount, $isBounty)
    {
        $assetTypeId = $this->repoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $accIdRepres = $this->repoAcc->getRepresentativeAccountId($assetTypeId);
        $accCust = $this->repoAcc->getByCustomerId($custId, $assetTypeId);
        $accIdCust = $accCust->getId();
        /* prepare bonus & fee transactions */
        $trans = [];
        $tranBonus = new ETrans();
        if ($isBounty) {
            $tranBonus->setDebitAccId($accIdRepres);
            $tranBonus->setCreditAccId($accIdCust);
            $tranBonus->setValue($amount);
            $note = "Ref. bonus bounty for sale order #$saleId.";
            $operType = Cfg::CODE_TYPE_OPER_BONUS_REF_BOUNTY;
        } else {
            $tranBonus->setDebitAccId($accIdCust);
            $tranBonus->setCreditAccId($accIdRepres);
            $tranBonus->setValue($amount);
            $note = "Ref. bonus fee for sale order #$saleId.";
            $operType = Cfg::CODE_TYPE_OPER_BONUS_REF_FEE;
        }
        $trans[] = $tranBonus;
        /* create operation */
        $req = new AReqOper();
        $req->setCustomerId($custId);
        $req->setOperationTypeCode($operType);
        $req->setTransactions($trans);
        $req->setOperationNote($note);
        /** @var ARespOper $resp */
        $resp = $this->servOper->exec($req);
        $result = $resp->getOperationId();
        return $result;
    }

}