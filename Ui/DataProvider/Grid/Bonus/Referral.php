<?php

/**
 * File creator: makhovdmitrii@inbox.ru
 */

namespace Praxigento\BonusReferral\Ui\DataProvider\Grid\Bonus;

use Magento\Sales\Api\Data\OrderInterface as DSaleOrder;
use Praxigento\Accounting\Config as Cfg;
use Praxigento\BonusReferral\Repo\Data\Registry as ERegistry;
use Praxigento\Downline\Repo\Data\Customer as EDwnlCust;

class Referral
    extends \Praxigento\Core\App\Ui\DataProvider\Grid\Query\Builder
{
    /**#@+ Tables aliases for external usage ('camelCase' naming) */
    const AS_CUSTOMER = 'ce';
    const AS_CUST_DWNL = 'cdwnl';
    const AS_REFERRAL = 're';
    const AS_REF_DWNL = 'rdwnl';
    const AS_REF_REG = 'brr';
    const AS_SALES_ORDER = 'so';
    /**#@- */

    /**#@+
     * Aliases for data attributes.
     */
    const A_AMOUNT_FEE = 'amountFee';
    const A_AMOUNT_TOTAL = 'amountTotal';
    const A_CUST_ID = 'custId';
    const A_CUST_MLM_ID = 'custMlmId';
    const A_CUST_NAME = 'custName';
    const A_OPERATION_REF = 'operationRef';
    const A_REF_ID = 'refId';
    const A_REF_MLM_ID = 'refMlmId';
    const A_REF_NAME = 'refName';
    const A_SALE_INC_ID = 'saleIncId';
    const A_SALE_REF = 'saleRef';
    const A_STATE = 'state';
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
                self::A_AMOUNT_FEE => self::AS_REF_REG . '.' . ERegistry::A_AMOUNT_FEE,
                self::A_AMOUNT_TOTAL => self::AS_REF_REG . '.' . ERegistry::A_AMOUNT_TOTAL,
                self::A_CUST_ID => self::AS_SALES_ORDER . '.' . Cfg::E_SALE_ORDER_A_CUSTOMER_ID,
                self::A_CUST_MLM_ID => self::AS_CUST_DWNL . '.' . EDwnlCust::A_MLM_ID,
                self::A_CUST_NAME => $this->getExpForCustName(),
                self::A_OPERATION_REF => self::AS_REF_REG . '.' . ERegistry::A_OPERATION_REF,
                self::A_REF_ID => self::AS_REF_REG . '.' . ERegistry::A_UPLINE_REF,
                self::A_REF_MLM_ID => self::AS_REF_DWNL . '.' . EDwnlCust::A_MLM_ID,
                self::A_REF_NAME => $this->getExpForRefName(),
                self::A_SALE_REF => self::AS_REF_REG . '.' . ERegistry::A_SALE_REF,
                self::A_SALE_INC_ID => self::AS_SALES_ORDER . '.' . DSaleOrder::INCREMENT_ID,
                self::A_STATE => self::AS_REF_REG . '.' . ERegistry::A_STATE,
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
        $asReg = self::AS_REF_REG;
        $asCust = self::AS_CUSTOMER;
        $asCustDwnl = self::AS_CUST_DWNL;
        $asRef = self::AS_REFERRAL;
        $asRefDwnl = self::AS_REF_DWNL;
        $asSalesOrder = self::AS_SALES_ORDER;

        /* SELECT FROM prxgt_bon_referral_reg */
        $tbl = $this->resource->getTableName(ERegistry::ENTITY_NAME);
        $as = $asReg;
        $cols = [
            self::A_SALE_REF => ERegistry::A_SALE_REF,
            self::A_CUST_ID => ERegistry::A_UPLINE_REF,
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
            self::A_REF_ID => DSaleOrder::CUSTOMER_ID,
            self::A_SALE_INC_ID => DSaleOrder::INCREMENT_ID
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID . '=' . $asReg . '.' . ERegistry::A_SALE_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN customer_entity as referral customer  */
        $tbl = $this->resource->getTableName(Cfg::ENTITY_MAGE_CUSTOMER);
        $as = $asRef;
        $exp = $this->getExpForRefName();
        $cols = [
            self::A_REF_NAME => $exp
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID . '=' . $asSalesOrder . '.' . Cfg::E_SALE_ORDER_A_CUSTOMER_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN prxgt_dwnl_customer for referral customer MLM ID */
        $tbl = $this->resource->getTableName(EDwnlCust::ENTITY_NAME);
        $as = $asRefDwnl;
        $cols = [
            self::A_REF_MLM_ID => EDwnlCust::A_MLM_ID
        ];
        $cond = "$as." . EDwnlCust::A_CUSTOMER_ID . "=$asRef." . Cfg::E_CUSTOMER_A_ENTITY_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN customer_entity as customer (bonus owner)*/
        $tbl = $this->resource->getTableName(Cfg::ENTITY_MAGE_CUSTOMER);
        $as = $asCust;
        $exp = $this->getExpForCustName();
        $cols = [
            self::A_CUST_NAME => $exp
        ];
        $cond = $as . '.' . Cfg::E_CUSTOMER_A_ENTITY_ID . '=' . $asReg . '.' . ERegistry::A_UPLINE_REF;
        $result->joinLeft([$as => $tbl], $cond, $cols);


        /* LEFT JOIN prxgt_dwnl_customer for customer MLM ID (bonus owner) */
        $tbl = $this->resource->getTableName(EDwnlCust::ENTITY_NAME);
        $as = $asCustDwnl;
        $cols = [
            self::A_CUST_MLM_ID => EDwnlCust::A_MLM_ID
        ];
        $cond = "$as." . EDwnlCust::A_CUSTOMER_ID . "=$asCust." . Cfg::E_CUSTOMER_A_ENTITY_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        return $result;
    }

    protected function getQueryTotal()
    {
        /* get query to select items */
        /** @var \Magento\Framework\DB\Select $result */
        $result = $this->getQueryItems();
        /* ... then replace "columns" part with own expression */
        $value = 'COUNT(' . self::AS_REF_REG . '.' . ERegistry::A_SALE_REF . ')';

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
