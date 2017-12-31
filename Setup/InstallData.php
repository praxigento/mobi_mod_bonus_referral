<?php
/**
 * Populate DB schema with module's initial data
 * .
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Setup;

use Praxigento\Accounting\Repo\Entity\Data\Type\Operation as TypeOperation;
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
            [TypeOperation::ATTR_CODE, TypeOperation::ATTR_NOTE],
            [
                [Cfg::CODE_TYPE_OPER_BONUS_REFERRAL, 'Change account balance by operator (adminhtml).']
            ]
        );
    }

    protected function _setup()
    {
        $this->_addAccountingOperationsTypes();
    }
}