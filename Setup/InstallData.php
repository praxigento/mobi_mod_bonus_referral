<?php
/**
 * Populate DB schema with module's initial data
 * .
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Setup;

use Praxigento\Accounting\Repo\Data\Type\Operation as TypeOperation;
use Praxigento\BonusReferral\Config as Cfg;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class InstallData extends \Praxigento\Core\App\Setup\Data\Base
{

    private function _addAccountingOperationsTypes()
    {
        $this->_conn->insertArray(
            $this->_resource->getTableName(TypeOperation::ENTITY_NAME),
            [TypeOperation::A_CODE, TypeOperation::A_NOTE],
            [
                [Cfg::CODE_TYPE_OPER_BONUS_REF_BOUNTY, 'Referral bonus bounty payment.'],
                [Cfg::CODE_TYPE_OPER_BONUS_REF_FEE, 'Referral bonus fee.']
            ]
        );
    }

    protected function _setup()
    {
        $this->_addAccountingOperationsTypes();
    }
}