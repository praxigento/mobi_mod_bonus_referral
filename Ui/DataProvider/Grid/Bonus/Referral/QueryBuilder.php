<?php

/**
 * File creator: makhovdmitrii@inbox.ru
 */

namespace Praxigento\BonusReferral\Ui\DataProvider\Grid\Bonus\Referral;

use Praxigento\Accounting\Config as Cfg;
use Praxigento\BonusReferral\Repo\Data\Registry as ERegistry;

class QueryBuilder
    extends \Praxigento\Core\App\Ui\DataProvider\Grid\Query\Builder
{
    /**#@+ Tables aliases for external usage ('camelCase' naming) */
    const AS_BON_REFERRAL_REG = 'brr';
    const AS_CUSTOMER = 'ce';
    const AS_REFERRAL = 're';
    const AS_SALES_ORDER = 'so';

    /**#@- */
    const A_AMOUNT_FEE = 'amountFee';
    const A_AMOUNT_TOTAL = 'amountTotal';
    const A_CUST_MLM_ID = 'custMlmId';
    const A_CUST_NAME = 'custName';
    const A_OPERATION_REF = 'operationRef';
    const A_REF_NAME = 'refName';
    /**#@+
     * Aliases for data attributes.
     */
    const A_SALE_REF = 'saleRef';
    const A_STATE = 'state';
    const A_UPLINE_REF = 'uplineRef';
    /**#@- */

    /**
     * Construct expression for customer name ("firstName lastName").
     */
    public function getExpForCustName()
    {
        $value = 'CONCAT(' . self::AS_CUSTOMER . '.' . Cfg::E_CUSTOMER_A_FIRSTNAME . ", ' ', " .
            self::AS_CUSTOMER . '.' . Cfg::E_CUSTOMER_A_LASTNAME . ')';
        $result = new \Praxigento\Core\App\Repo\Query\Expression($value);
        return $result;
    }

    /**
     * Construct expression for referral name ("firstName lastName").
     */
    public function getExpForRefName()
    {
        $value = 'CONCAT(' . self::AS_REFERRAL . '.' . Cfg::E_CUSTOMER_A_FIRSTNAME . ", ' ', " .
            self::AS_REFERRAL . '.' . Cfg::E_CUSTOMER_A_LASTNAME . ')';
        $result = new \Praxigento\Core\App\Repo\Query\Expression($value);
        return $result;
    }

    protected function getMapper()
    {
        if (is_null($this->mapper)) {
            $map = [
                self::A_SALE_REF => self::AS_BON_REFERRAL_REG . '.' . ERegistry::A_SALE_REF,
                self::A_UPLINE_REF => self::AS_BON_REFERRAL_REG . '.' . ERegistry::A_UPLINE_REF,
                self::A_OPERATION_REF => self::AS_BON_REFERRAL_REG . '.' . ERegistry::A_OPERATION_REF,
                self::A_AMOUNT_TOTAL => self::AS_BON_REFERRAL_REG . '.' . ERegistry::A_AMOUNT_TOTAL,
                self::A_AMOUNT_FEE => self::AS_BON_REFERRAL_REG . '.' . ERegistry::A_AMOUNT_FEE,
                self::A_STATE => self::AS_BON_REFERRAL_REG . '.' . ERegistry::A_STATE,
                self::A_CUST_MLM_ID => self::AS_SALES_ORDER . '.' . Cfg::E_SALE_ORDER_A_CUSTOMER_ID,
                self::A_CUST_NAME => $this->getExpForCustName(),
                self::A_REF_NAME => $this->getExpForRefName()
            ];
            $this->mapper = new \Praxigento\Core\App\Repo\Query\Criteria\Def\Mapper($map);
        }
        $result = $this->mapper;
        return $result;
    }

    protected function getQueryItems()
    {
        $result = $this->conn->select();
        /* define tables aliases for internal usage (in this method) */
        $asRefReg = self::AS_BON_REFERRAL_REG;
        $asCust = self::AS_CUSTOMER;
        $asReferral = self::AS_REFERRAL;
        $asSalesOrder = self::AS_SALES_ORDER;

        /* SELECT FROM prxgt_bon_referral_reg */
        $tbl = $this->resource->getTableName(ERegistry::ENTITY_NAME);
        $as = $asRefReg;
        $cols = [
            self::A_SALE_REF => ERegistry::A_SALE_REF,
            self::A_UPLINE_REF => ERegistry::A_UPLINE_REF,
            self::A_OPERATION_REF => ERegistry::A_OPERATION_REF,
            self::A_STATE => ERegistry::A_STATE,
            self::A_AMOUNT_TOTAL => ERegistry::A_AMOUNT_TOTAL,
            self::A_AMOUNT_FEE => ERegistry::A_AMOUNT_FEE
        ];
        $result->from([$as => $tbl], $cols);

        /* LEFT JOIN sales_order  */
        $tbl = $this->resource->getTableName(Cfg::ENTITY_MAGE_SALES_ORDER);
        $as = $asSalesOrder;
        $cols = [
            self::A_CUST_MLM_ID => Cfg::E_SALE_ORDER_A_CUSTOMER_ID
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID . '=' . $asRefReg . '.' . ERegistry::A_SALE_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN customer_entity Order Customer */
        $tbl = $this->resource->getTableName(Cfg::ENTITY_MAGE_CUSTOMER);
        $as = $asCust;
        $exp = $this->getExpForCustName();
        $cols = [
            self::A_CUST_NAME => $exp
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID . '=' . $asSalesOrder . '.' . Cfg::E_SALE_ORDER_A_CUSTOMER_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN customer_entity Referral */
        $tbl = $this->resource->getTableName(Cfg::ENTITY_MAGE_CUSTOMER);
        $as = $asReferral;
        $exp = $this->getExpForRefName();
        $cols = [
            self::A_REF_NAME => $exp
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID . '=' . $asRefReg . '.' . ERegistry::A_UPLINE_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        return $result;
    }

    protected function getQueryTotal()
    {
        /* get query to select items */
        /** @var \Magento\Framework\DB\Select $result */
        $result = $this->getQueryItems();
        /* ... then replace "columns" part with own expression */
        $value = 'COUNT(' . self::AS_BON_REFERRAL_REG . '.' . ERegistry::A_SALE_REF . ')';

        /**
         * See method \Magento\Framework\DB\Select\ColumnsRenderer::render:
         */
        /**
         * if ($column instanceof \Zend_Db_Expr) {...}
         */
        $exp = new \Praxigento\Core\App\Repo\Query\Expression($value);
        /**
         *  list($correlationName, $column, $alias) = $columnEntry;
         */
        $entry = [null, $exp, null];
        $cols = [$entry];
        $result->setPart('columns', $cols);
        return $result;
    }
}
