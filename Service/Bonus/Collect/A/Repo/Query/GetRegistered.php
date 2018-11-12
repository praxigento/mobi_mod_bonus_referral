<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Bonus\Collect\A\Repo\Query;

use Magento\Sales\Model\Order\Invoice as AInvoice;
use Praxigento\BonusReferral\Config as Cfg;
use Praxigento\BonusReferral\Repo\Data\Registry as ERegistry;

/**
 * Query to get data for referral bonus from registry.
 */
class GetRegistered
    extends \Praxigento\Core\App\Repo\Query\Builder
{

    /** Tables aliases for external usage ('camelCase' naming) */
    const AS_CUST = 'cust';
    const AS_INV = 'invoice';
    const AS_ORDR = 'order';
    const AS_REG = 'registry';

    /** Columns/expressions aliases for external usage ('camelCase' naming) */
    const A_BONUS = 'bonus';
    const A_CUST_ID = 'customerId';
    const A_DATE_PAID = 'datePaid';
    const A_FEE = 'fee';
    const A_REFERRAL = 'referral';
    const A_SALE_ID = 'saleId';
    const A_SALE_INC = 'saleInc';

    /** Bound variables names ('camelCase' naming) */
    const BND_DATE_PAID = 'datePaid';

    /** Entities are used in the query */
    const E_CUSTOMER = Cfg::ENTITY_MAGE_CUSTOMER;
    const E_INVOICE = Cfg::ENTITY_MAGE_SALES_INVOICE;
    const E_ORDER = Cfg::ENTITY_MAGE_SALES_ORDER;
    const E_REGISTRY = ERegistry::ENTITY_NAME;

    public function build(\Magento\Framework\DB\Select $source = null)
    {
        /* this is root query builder (started from SELECT) */
        $result = $this->conn->select();

        /* define tables aliases for internal usage (in this method) */
        $asCust = self::AS_CUST;
        $asInv = self::AS_INV;
        $asOrdr = self::AS_ORDR;
        $asReg = self::AS_REG;

        /* FROM prxgt_bon_referral_reg */
        $tbl = $this->resource->getTableName(self::E_REGISTRY);
        $as = $asReg;
        $cols = [
            self::A_SALE_ID => ERegistry::A_SALE_REF,
            self::A_CUST_ID => ERegistry::A_UPLINE_REF,
            self::A_BONUS => ERegistry::A_AMOUNT_TOTAL,
            self::A_FEE => ERegistry::A_AMOUNT_FEE
        ];
        $result->from([$as => $tbl], $cols);

        /* LEFT JOIN sales_order */
        $tbl = $this->resource->getTableName(self::E_ORDER);
        $as = $asOrdr;
        $cols = [
            self::A_SALE_INC => Cfg::E_SALE_ORDER_A_INCREMENT_ID
        ];
        $cond = $as . '.' . Cfg::E_SALE_ORDER_A_ENTITY_ID
            . '=' . $asReg . '.' . ERegistry::A_SALE_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN sales_invoice */
        $tbl = $this->resource->getTableName(self::E_INVOICE);
        $as = $asInv;
        $cols = [
            self::A_DATE_PAID => Cfg::E_SALE_INVOICE_A_CREATED_AT
        ];
        $cond = $as . '.' . Cfg::E_SALE_INVOICE_A_ORDER_ID
            . '=' . $asReg . '.' . ERegistry::A_SALE_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN customer_entity */
        $tbl = $this->resource->getTableName(self::E_CUSTOMER);
        $as = $asCust;
        $exp = $this->getExpForCustName();
        $cols = [
            self::A_REFERRAL => $exp
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID
            . '=' . $asOrdr . '.' . Cfg::E_SALE_ORDER_A_CUSTOMER_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* query tuning */
        $byDatePaid = "$asInv." . Cfg::E_SALE_INVOICE_A_CREATED_AT . "<=:" . self::BND_DATE_PAID;
        $byState = "$asReg." . ERegistry::A_STATE . "='" . ERegistry::STATE_PENDING . "'";
        $byInvState = "$asInv." . Cfg::E_SALE_INVOICE_A_STATE . "=" . AInvoice::STATE_PAID;
        $result->where("($byDatePaid) AND ($byState) AND ($byInvState)");

        return $result;
    }

    public function getExpForCustName()
    {
        $value = 'CONCAT(' . self::AS_CUST . '.' . Cfg::E_CUSTOMER_A_FIRSTNAME . ", ' ', " .
            self::AS_CUST . '.' . Cfg::E_CUSTOMER_A_LASTNAME . ')';
        $result = new \Praxigento\Core\App\Repo\Query\Expression($value);
        return $result;
    }
}