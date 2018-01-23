<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Bonus;

use Praxigento\BonusReferral\Config as Cfg;
use Praxigento\BonusReferral\Repo\Entity\Data\Registry as ERegistry;
use Praxigento\BonusReferral\Service\Bonus\Collect\Own\Repo\Query\GetRegistered as QBGetRegs;
use Praxigento\BonusReferral\Service\Bonus\Collect\Request as ARequest;
use Praxigento\BonusReferral\Service\Bonus\Collect\Response as AResponse;

class Collect
{
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Core\Api\Helper\Date */
    private $hlpDate;
    /** @var \Praxigento\Core\Api\Helper\Format */
    private $hlpFormat;
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect\Own\CreateOperation */
    private $ownOperCreate;
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect\Own\Repo\Query\GetRegistered */
    private $qbGetRegs;
    /** @var \Praxigento\BonusReferral\Repo\Entity\Registry */
    private $repoReg;

    public function __construct(
        \Praxigento\BonusReferral\Repo\Entity\Registry $repoReg,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        \Praxigento\Core\Api\Helper\Date $hlpDate,
        \Praxigento\Core\Api\Helper\Format $hlpFormat,
        QBGetRegs $qbGetRegs,
        \Praxigento\BonusReferral\Service\Bonus\Collect\Own\CreateOperation $ownOperCreate
    ) {
        $this->repoReg = $repoReg;
        $this->hlpConfig = $hlpConfig;
        $this->hlpDate = $hlpDate;
        $this->hlpFormat = $hlpFormat;
        $this->qbGetRegs = $qbGetRegs;
        $this->ownOperCreate = $ownOperCreate;
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
                $operId = $this->ownOperCreate->exec($saleId, $custId, $bonus, true);
                /* update status of the referral bonus in registry*/
                $this->updateRegistry($saleId, $operId);
                /* referral bonus fee */
                if (abs($fee) > Cfg::DEF_ZERO) {
                    $this->ownOperCreate->exec($saleId, $custId, $fee, false);
                }
            }
        }
        /** compose result */
        $result = new AResponse();
        return $result;
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

    private function updateRegistry($saleId, $operId)
    {
        $entity = new ERegistry();
        $entity->setOperationRef($operId);
        $entity->setState(ERegistry::STATE_PAID);
        $this->repoReg->updateById($saleId, $entity);
    }
}