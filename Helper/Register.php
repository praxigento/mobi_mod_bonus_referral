<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2019
 */

namespace Praxigento\BonusReferral\Helper;

/**
 * Default implementation returns upline customer for the customer that create sale.
 */
class Register
    implements \Praxigento\BonusReferral\Api\Helper\Register
{
    /** @var \Praxigento\Downline\Repo\Dao\Customer */
    private $daoDwnl;

    public function __construct(
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnl
    ) {
        $this->daoDwnl = $daoDwnl;
    }

    public function getBeneficiaryId($sale)
    {
        $result = null;
        if ($sale instanceof \Magento\Sales\Model\Order) {
            $custId = $sale->getCustomerId();
            $result = $this->getUplineId($custId);
        }
        return $result;
    }

    /**
     * Get upline ID for given customer.
     *
     * @param int $custId
     * @return int
     */
    private function getUplineId($custId)
    {
        /** @var \Praxigento\Downline\Repo\Data\Customer $entity */
        $entity = $this->daoDwnl->getById($custId);
        $result = $entity->getParentRef();
        return $result;
    }
}