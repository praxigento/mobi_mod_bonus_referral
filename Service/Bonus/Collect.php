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
     * More than one invoice can relate for one sale order. We should not pay bonus twice ore more.
     *
     * @param int $saleId
     * @return bool
     */
    private function canProcess($saleId)
    {
        $result = false;
        $entity = $this->daoReg->getById($saleId);
        if ($entity) {
            $state = $entity->getState();
            $result = ($state == ERegistry::STATE_PENDING);
        }
        return $result;
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
                $bonus = $item[QBGetRegs::A_BONUS];
                $custId = $item[QBGetRegs::A_CUST_ID];
                $fee = $item[QBGetRegs::A_FEE];
                $referral = $item[QBGetRegs::A_REFERRAL];
                $saleId = $item[QBGetRegs::A_SALE_ID];
                $saleIncId = $item[QBGetRegs::A_SALE_INC];
                $this->logger->info(
                    "Processing referral bonus for customer #$custId (sale: $saleIncId/$saleId). Bonus amount: $bonus; fee: $fee."
                );
                $canProcess = $this->canProcess($saleId);
                if ($canProcess) {
                    $operId = $this->ownOperCreate->exec($saleId, $saleIncId, $custId, $referral, $bonus, true);
                    /* update status of the referral bonus in registry*/
                    $this->updateRegistry($saleId, $operId);
                    /* referral bonus fee */
                    if (abs($fee) > Cfg::DEF_ZERO) {
                        $this->ownOperCreate->exec($saleId, $saleIncId, $custId, $referral, $fee, false);
                    }
                } else {
                    $this->logger->info("Cannot process referral bonus for sale #$saleId. Wrong state in registry.");
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
        $dt = $this->hlpDate->getUtcNow();
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