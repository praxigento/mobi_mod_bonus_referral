<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Bonus\Collect\A;

use Praxigento\Accounting\Api\Service\Operation\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Response as ARespOper;
use Praxigento\Accounting\Repo\Data\Transaction as ETrans;
use Praxigento\BonusReferral\Config as Cfg;

class CreateOperation
{
    /** @var \Praxigento\Accounting\Repo\Dao\Account */
    private $daoAcc;
    /** @var \Praxigento\Accounting\Repo\Dao\Type\Asset */
    private $daoAssetType;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\Accounting\Repo\Dao\Account $daoAcc,
        \Praxigento\Accounting\Repo\Dao\Type\Asset $daoAssetType,
        \Praxigento\Accounting\Api\Service\Operation $servOper
    ) {
        $this->logger = $logger;
        $this->daoAcc = $daoAcc;
        $this->daoAssetType = $daoAssetType;
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
        $assetTypeId = $this->daoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET);
        $accIdSys = $this->daoAcc->getSystemAccountId($assetTypeId);
        $accCust = $this->daoAcc->getByCustomerId($custId, $assetTypeId);
        $accIdCust = $accCust->getId();
        /* prepare bonus & fee transactions */
        $trans = [];
        $tranBonus = new ETrans();
        if ($isBounty) {
            $note = "Ref. bonus bounty for sale order #$saleId.";
            $operType = Cfg::CODE_TYPE_OPER_BONUS_REF_BOUNTY;
            $accDebit = $accIdSys;
            $accCredit = $accIdCust;
        } else {
            $note = "Ref. bonus fee for sale order #$saleId.";
            $operType = Cfg::CODE_TYPE_OPER_BONUS_REF_FEE;
            $accDebit = $accIdCust;
            $accCredit = $accIdSys;
        }
        $tranBonus->setDebitAccId($accDebit);
        $tranBonus->setCreditAccId($accCredit);
        $tranBonus->setValue($amount);
        $tranBonus->setNote($note);
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
        if ($isBounty) {
            $msg = "Referral bonus operation is created (#$result)";
        } else {
            $msg = "Referral bonus fee operation is created (#$result)";
        }
        $this->logger->info($msg);
        return $result;
    }

}