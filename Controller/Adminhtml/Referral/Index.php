<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Controller\Adminhtml\Referral;

use Praxigento\BonusReferral\Config as Cfg;

class Index
    extends \Praxigento\Core\App\Action\Back\Base
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    )
    {
        $aclResource = Cfg::MODULE . '::' . Cfg::ACL_BONUS_REFERRAL;
        $activeMenu = Cfg::MODULE . '::' . Cfg::MENU_BONUS_REFERRAL;
        $breadcrumbLabel = 'Referral Bonus';
        $breadcrumbTitle = 'Referral Bonus';
        $pageTitle = 'Referral Bonus';
        parent::__construct(
            $context,
            $aclResource,
            $activeMenu,
            $breadcrumbLabel,
            $breadcrumbTitle,
            $pageTitle
        );
    }
}