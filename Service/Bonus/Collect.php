<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Bonus;

use Praxigento\BonusReferral\Api\Service\Bonus\Collect\Request as ARequest;
use Praxigento\BonusReferral\Api\Service\Bonus\Collect\Response as AResponse;
use Praxigento\BonusReferral\Config as Cfg;
use Praxigento\BonusReferral\Repo\Data\Registry as ERegistry;
use Praxigento\BonusReferral\Service\Bonus\Collect\A\Repo\Query\GetRegistered as QBGetRegs;

class Collect
    implements \Praxigento\BonusReferral\Api\Service\Bonus\Collect
{
    /** @var \Praxigento\BonusReferral\Repo\Dao\Registry */
    private $daoReg;
    /** @var \Praxigento\BonusReferral\Helper\Config */
    private $hlpConfig;
    /** @var \Praxigento\Core\Api\Helper\Date */
    private $hlpDate;
    /** @var \Praxigento\Core\Api\Helper\Format */
    private $hlpFormat;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect\A\CreateOperation */
    private $ownOperCreate;
    /** @var \Praxigento\BonusReferral\Service\Bonus\Collect\A\Repo\Query\GetRegistered */
    private $qbGetRegs;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\BonusReferral\Repo\Dao\Registry $daoReg,
        \Praxigento\BonusReferral\Helper\Config $hlpConfig,
        \Praxigento\Core\Api\Helper\Date $hlpDate,
        \Praxigento\Core\Api\Helper\Format $hlpFormat,
        QBGetRegs $qbGetRegs,
        \Praxigento\BonusReferral\Service\Bonus\Collect\A\CreateOperation $ownOperCreate
    ) {
        $this->logger = $logger;
        $this->daoReg = $daoReg;
        $this->hlpConfig = $hlpConfig;
        $this->hlpDate = $hlpDate;
        $this->hlpFormat = $hlpFormat;
        $this->qbGetRegs = $qbGetRegs;
        $this->ownOperCreate = $ownOperCreate;
    }


    /**
     * @param ARequest $request
     * @return AResponse
     * @throws \Exception
     */
    public function exec($request)
    {
        /** define local working data */
        assert($request instanceof ARequest);

        /** perform processing */
        $isEnabled = $this->hlpConfig->getBonusEnabled();
        if ($isEnabled) {
            $dateUpTo = $this->getDateUpTo();
            $registered = $this->getRegistered($dateUpTo);
            $total = count($registered);
            $this->logger->info("There are '$total' registered bonus up to '$dateUpTo'.");
            foreach ($registered as $item) {
                $custId = $item[QBGetRegs::A_CUST_ID];
                $saleId = $item[QBGetRegs::A_SALE_ID];
                $bonus = $item[QBGetRegs::A_BONUS];
                $fee = $item[QBGetRegs::A_FEE];
                $this->logger->info(
                    "Processing referral bonus for customer #$custId (sale: $saleId). Bonus amount: $bonus; fee: $fee."
                );
                $operId = $this->ownOperCreate->exec($saleId, $custId, $bonus, true);
                /* update status of the referral bonus in registry*/
                $this->updateRegistry($saleId, $operId);
                /* referral bonus fee */
                if (abs($fee) > Cfg::DEF_ZERO) {
                    $this->ownOperCreate->exec($saleId, $custId, $fee, false);
                }
            }
        } else {
            $this->logger->warning("Referral bonus is disabled. Please, enable it in "
                . "'Store / Config / MOBI / Downline / Referral Bonus'.");
        }
        /** compose result */
        $result = new AResponse();
        return $result;
    }

    private function getDateUpTo()
    {
        $delay = $this->hlpConfig->getBonusPayoutDelay();
        $dt = $this->hlpDate->getMageNow();
        $ts = $dt->getTimestamp();
        $ts -= ($delay * 3600 * 24);
        $dt->setTimestamp($ts);
        $result = $this->hlpFormat->dateTimeForDb($dt);
        return $result;
    }

    private function getRegistered($dateUpTo)
    {
        $query = $this->qbGetRegs->build();
        $conn = $query->getConnection();
        $bind = [
            QBGetRegs::BND_DATE_PAID => $dateUpTo
        ];
        $result = $conn->fetchAll($query, $bind);
        return $result;
    }

    private function updateRegistry($saleId, $operId)
    {
        $entity = new ERegistry();
        $entity->setOperationRef($operId);
        $entity->setState(ERegistry::STATE_PAID);
        $this->daoReg->updateById($saleId, $entity);
    }
}