<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Bonus;

use Praxigento\Accounting\Api\Service\Operation\Request as AReqOper;
use Praxigento\Accounting\Api\Service\Operation\Response as ARespOper;
use Praxigento\Accounting\Repo\Entity\Data\Transaction as ETrans;
use Praxigento\BonusReferral\Config as Cfg;
use Praxigento\BonusReferral\Repo\Entity\Data\Registry as ERegistry;
use Praxigento\BonusReferral\Service\Bonus\Collect\Repo\Query\GetRegistered as QBGetRegs;
use Praxigento\BonusReferral\Service\Bonus\Collect\Request as ARequest;
use Praxigento\BonusReferral\Service\Bonus\Collect\Response as AResponse;

class Collect
{
    /** @var int ID for WALLET ACTIVE asset */
    private $cacheAssetTypeId;
    /** @var int ID for representative account ID for WALLET ACTIVE asset */
    private $cacheRepresAccId;
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Core\Api\Helper\Date */
    private $hlpDate;
    /** @var \Praxigento\Core\Api\Helper\Format */
    private $hlpFormat;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect\Repo\Query\GetRegistered */
    private $qbGetRegs;
    /** @var \Praxigento\Accounting\Repo\Entity\Account */
    private $repoAcc;
    /** @var \Praxigento\Accounting\Repo\Entity\Type\Asset */
    private $repoAssetType;
    /** @var \Praxigento\BonusReferral\Repo\Entity\Registry */
    private $repoReg;
    /** @var \Praxigento\Accounting\Api\Service\Account\Get */
    private $servAccGet;
    /** @var \Praxigento\Accounting\Api\Service\Operation */
    private $servOper;

    /**
     * TODO: extract operations processing into internal subroutine.
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Praxigento\Accounting\Repo\Entity\Account $repoAcc,
        \Praxigento\Accounting\Repo\Entity\Type\Asset $repoAssetType,
        \Praxigento\BonusReferral\Repo\Entity\Registry $repoReg,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        \Praxigento\Core\Api\Helper\Date $hlpDate,
        \Praxigento\Core\Api\Helper\Format $hlpFormat,
        \Praxigento\Accounting\Api\Service\Account\Get $servAccGet,
        \Praxigento\Accounting\Api\Service\Operation $servOper,
        QBGetRegs $qbGetRegs
    ) {
        $this->logger = $logger;
        $this->repoAcc = $repoAcc;
        $this->repoAssetType = $repoAssetType;
        $this->repoReg = $repoReg;
        $this->hlpConfig = $hlpConfig;
        $this->hlpDate = $hlpDate;
        $this->hlpFormat = $hlpFormat;
        $this->servAccGet = $servAccGet;
        $this->servOper = $servOper;
        $this->qbGetRegs = $qbGetRegs;
    }


    private function createOperation($custId, $bonus, $fee)
    {
        $assetTypeId = $this->getAssetTypeId();
        $accIdRepres = $this->getRepresAccId();
        $accCust = $this->repoAcc->getByCustomerId($custId, $assetTypeId);
        $accIdCust = $accCust->getId();
        /* prepare bonus & fee transactions */
        $trans = [];
        $tranBonus = new ETrans();
        $tranBonus->setDebitAccId($accIdRepres);
        $tranBonus->setCreditAccId($accIdCust);
        $tranBonus->setValue($bonus);
        $trans[] = $tranBonus;
        if ($fee > Cfg::DEF_ZERO) {
            $tranFee = new ETrans();
            $tranFee->setDebitAccId($accIdCust);
            $tranFee->setCreditAccId($accIdRepres);
            $tranFee->setValue($fee);
            $trans[] = $tranFee;
        }
        /* create operation */
        $req = new AReqOper();
        $req->setCustomerId($custId);
        $req->setOperationTypeCode(Cfg::CODE_TYPE_OPER_BONUS_REF_BOUNTY);
        $req->setTransactions($trans);
        /** @var ARespOper $resp */
        $resp = $this->servOper->exec($req);
        $result = $resp->getOperationId();
        return $result;
    }

    /**
     * @param ARequest $request
     * @return AResponse
     */
    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);

        /** perform processing */
        $isEnabled = $this->hlpConfig->getBonusEnabled();
        if ($isEnabled) {
            $dateFrom = $this->getDateFrom();
            $registered = $this->getRegistered($dateFrom);
            foreach ($registered as $item) {
                $custId = $item[QBGetRegs::A_CUST_ID];
                $saleId = $item[QBGetRegs::A_SALE_ID];
                $bonus = $item[QBGetRegs::A_BONUS];
                $fee = $item[QBGetRegs::A_FEE];
                $operId = $this->createOperation($custId, $bonus, $fee);
                /* update status of the referral bonus in registry*/
                $this->updateRegistry($saleId, $operId);
            }
        }
        /** compose result */
        $result = new AResponse();
        return $result;
    }

    private function getAssetTypeId()
    {
        if (is_null($this->cacheAssetTypeId)) {
            $this->cacheAssetTypeId = $this->repoAssetType->getIdByCode(Cfg::CODE_TYPE_ASSET_WALLET_ACTIVE);
        }
        return $this->cacheAssetTypeId;
    }

    private function getDateFrom()
    {
        $delay = $this->hlpConfig->getBonusPayoutDelay();
        $dt = $this->hlpDate->getMageNow();
        $ts = $dt->getTimestamp();
        $ts -= ($delay * 3600 * 24);
        $dt->setTimestamp($ts);
        $result = $this->hlpFormat->dateTimeForDb($dt);
        return $result;
    }

    private function getRegistered($dateFrom)
    {
        $query = $this->qbGetRegs->build();
        $conn = $query->getConnection();
        $bind = [
            QBGetRegs::BND_DATE_PAID => $dateFrom
        ];
        $result = $conn->fetchAll($query, $bind);
        return $result;
    }

    private function getRepresAccId()
    {
        if (is_null($this->cacheRepresAccId)) {
            $assetId = $this->getAssetTypeId();
            $this->cacheRepresAccId = $this->repoAcc->getRepresentativeAccountId($assetId);
        }
        return $this->cacheRepresAccId;
    }

    private function updateRegistry($saleId, $operId)
    {
        $entity = new ERegistry();
        $entity->setOperationRef($operId);
        $entity->setState(ERegistry::STATE_PAID);
        $this->repoReg->updateById($saleId, $entity);
    }
}