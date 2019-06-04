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
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $repoCust;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $repoCust,
        \Praxigento\Downline\Repo\Dao\Customer $daoDwnl
    ) {
        $this->repoCust = $repoCust;
        $this->daoDwnl = $daoDwnl;
    }

    /**
     * Customer's sponsor (upline) is referral bonus beneficiary by default.
     *
     * @inheritDoc
     */
    public function getBeneficiaryId($sale)
    {
        $beneficiaryId = $bnfGroupId = null;
        if ($sale instanceof \Magento\Sales\Model\Order) {
            $custId = $sale->getCustomerId();
            $beneficiaryId = $this->getUplineId($custId);
            $beneficiary = $this->repoCust->getById($beneficiaryId);
            $bnfGroupId = $beneficiary->getGroupId();
        }
        return [$beneficiaryId, $bnfGroupId];
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