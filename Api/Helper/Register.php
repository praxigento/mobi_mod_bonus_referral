<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2019
 */

namespace Praxigento\BonusReferral\Api\Helper;


interface Register
{
    /**
     * Return customer ID for referral bonus beneficiary (not PV receiver).
     *
     * @param \Magento\Sales\Model\Order $sale
     * @return array [beneficiaryId, uplineId]
     */
    public function getBeneficiaryId($sale);

}