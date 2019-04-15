<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Praxigento\BonusReferral\Block\Adminhtml\Sales\Order\View;

use Praxigento\BonusReferral\Config as Cfg;
use Praxigento\BonusReferral\Repo\Data\Registry as EBonusReg;

/**
 * Add referral bonus data to sale order form.
 */
class Info
    extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /** @var EBonusReg */
    private $cacheBonusReg;
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnlCust;
    /** @var \Praxigento\Core\Api\App\Repo\Generic */
    private $daoGeneric;
    /** @var \Praxigento\Pv\Repo\Dao\Sale */
    private $daoPvSale;
    /** @var \Praxigento\BonusReferral\Repo\Dao\Registry */
    private $daoRegistry;

    public function __construct(
        \Praxigento\Core\Api\App\Repo\Generic $daoGeneric,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnlCust,
        \Praxigento\Pv\Repo\Dao\Sale $daoPvSale,
        \Praxigento\BonusReferral\Repo\Dao\Registry $daoRegistry,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);
        $this->daoGeneric = $daoGeneric;
        $this->daoDwnlCust = $daoDwnlCust;
        $this->daoPvSale = $daoPvSale;
        $this->daoRegistry = $daoRegistry;
    }

    public function getAmountFee()
    {
        $amount = $this->cacheBonusReg->getAmountFee();
        $result = number_format($amount, 2);;
        return $result;
    }

    public function getAmountTotal()
    {
        $amount = $this->cacheBonusReg->getAmountTotal();
        $result = number_format($amount, 2);;
        return $result;
    }

    public function getOperationId()
    {
        $result = $this->cacheBonusReg->getOperationRef();
        return $result;
    }

    public function getSalePv()
    {
        $result = 0;
        $order = $this->getOrder();
        $orderId = $order->getId();
        $found = $this->daoPvSale->getById($orderId);
        if ($found) {
            $result = $found->getTotal();
        }
        $result = number_format($result, 2);
        return $result;
    }

    public function getState()
    {
        $result = $this->cacheBonusReg->getState();
        return $result;
    }

    public function getUplineMlmId()
    {
        $uplineId = $this->cacheBonusReg->getUplineRef();
        $found = $this->daoDwnlCust->getById($uplineId);
        $result = $found->getMlmId();
        return $result;
    }

    public function getUplineName()
    {
        $uplineId = $this->cacheBonusReg->getUplineRef();
        $entity = Cfg::ENTITY_MAGE_CUSTOMER;
        $pk = [Cfg::E_CUSTOMER_A_ENTITY_ID => $uplineId];
        $found = $this->daoGeneric->getEntityByPk($entity, $pk);
        $first = $found[Cfg::E_CUSTOMER_A_FIRSTNAME];
        $last = $found[Cfg::E_CUSTOMER_A_LASTNAME];
        $result = trim("$first $last");
        return $result;
    }

    public function getUplineViewUrl()
    {
        $uplineId = $this->cacheBonusReg->getUplineRef();
        $result = $this->getUrl('customer/index/edit', ['id' => $uplineId]);
        return $result;
    }

    public function hasBonus()
    {
        $result = false;
        $sale = $this->getOrder();
        $saleId = $sale->getId();
        $found = $this->daoRegistry->getById($saleId);
        if ($found) {
            $result = true;
            $this->cacheBonusReg = $found;
        }
        return $result;
    }
}