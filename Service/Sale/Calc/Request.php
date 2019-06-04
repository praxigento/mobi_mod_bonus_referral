<?php
/**
 *
 */

namespace Praxigento\BonusReferral\Service\Sale\Calc;


/**
 * @method int getBeneficiaryGroupId() use this group for group prices instead of own customer's group
 * @method int getBeneficiaryId()
 * @method \Magento\Sales\Model\Order getSaleOrder()
 * @method void setBeneficiaryGroupId(int $data)
 * @method void setBeneficiaryId(int $data)
 * @method void setSaleOrder(\Magento\Sales\Model\Order $data)
 */
class Request
    extends \Praxigento\Core\App\Service\Request
{

}
