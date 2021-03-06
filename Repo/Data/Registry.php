<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Repo\Data;

/**
 * Registry for referral bonus entries.
 */
class Registry
    extends \Praxigento\Core\App\Repo\Data\Entity\Base
{
    const A_AMOUNT_FEE = 'amount_fee';
    const A_AMOUNT_TOTAL = 'amount_total';
    const A_OPERATION_REF = 'operation_ref';
    const A_SALE_REF = 'sale_ref';
    const A_STATE = 'state';
    const A_UPLINE_REF = 'upline_ref';
    const ENTITY_NAME = 'prxgt_bon_referral_reg';
    const STATE_PAID = 'paid';
    const STATE_PENDING = 'pending';
    const STATE_REGISTERED = 'registered';
    const STATE_REVERTED = 'reverted';

    /** @return float */
    public function getAmountFee()
    {
        $result = parent::get(self::A_AMOUNT_FEE);
        return $result;
    }

    /** @return float */
    public function getAmountTotal()
    {
        $result = parent::get(self::A_AMOUNT_TOTAL);
        return $result;
    }

    /** @return int */
    public function getOperationRef()
    {
        $result = parent::get(self::A_OPERATION_REF);
        return $result;
    }

    public static function getPrimaryKeyAttrs()
    {
        return [self::A_SALE_REF];
    }

    /** @return int */
    public function getSaleRef()
    {
        $result = parent::get(self::A_SALE_REF);
        return $result;
    }

    /** @return string */
    public function getState()
    {
        $result = parent::get(self::A_STATE);
        return $result;
    }

    /** @return int */
    public function getUplineRef()
    {
        $result = parent::get(self::A_UPLINE_REF);
        return $result;
    }

    /** @param float $data */
    public function setAmountFee($data)
    {
        parent::set(self::A_AMOUNT_FEE, $data);
    }

    /** @param float $data */
    public function setAmountTotal($data)
    {
        parent::set(self::A_AMOUNT_TOTAL, $data);
    }

    /** @param int $data */
    public function setOperationRef($data)
    {
        parent::set(self::A_OPERATION_REF, $data);
    }

    /** @param int $data */
    public function setSaleRef($data)
    {
        parent::set(self::A_SALE_REF, $data);
    }

    /** @param string $data */
    public function setState($data)
    {
        parent::set(self::A_STATE, $data);
    }

    /** @param int $data */
    public function setUplineRef($data)
    {
        parent::set(self::A_UPLINE_REF, $data);
    }

}