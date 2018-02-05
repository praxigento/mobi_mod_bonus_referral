<?php
/**
 * Module's configuration (hard-coded).
 *
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral;

class Config
    extends \Praxigento\BonusBase\Config
{
    const ACL_BONUS_REFERRAL = 'admin_bonus_referral';
    const CODE_TYPE_OPER_BONUS_REF_BOUNTY = 'BONUS_REF_BOUNTY';
    const CODE_TYPE_OPER_BONUS_REF_FEE = 'BONUS_REF_FEE';
    const MENU_BONUS_REFERRAL = self::ACL_BONUS_REFERRAL;

    const MODULE = 'Praxigento_BonusReferral';
}