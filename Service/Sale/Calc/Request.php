<?php
/**
 *
 */

namespace Praxigento\BonusReferral\Service\Sale\Calc;


/**
 * @method int getBeneficiaryId()
 * @method int getCustomerGroupId() use this group for group prices instead of own customer's group
 * @method \Magento\Sales\Model\Order getSaleOrder()
 * @method void setBeneficiaryId(int $data)
 * @method void setCustomerGroupId(int $data)
 * @method void setSaleOrder(\Magento\Sales\Model\Order $data)
 */
class Request
    extends \Praxigento\Core\App\Service\Request
{

}
