<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Service\Sale\Calc\Repo\Query\Product;


use Praxigento\BonusReferral\Config as Cfg;
use Praxigento\Warehouse\Repo\Entity\Data\Group\Price as EGroupPrice;
use Praxigento\Warehouse\Repo\Entity\Data\Stock\Item as EWrhsItem;

/**
 * Select warehouse price & group price for product/stock/group.
 */
class Prices
    extends \Praxigento\Core\App\Repo\Query\Builder
{
    /** Tables aliases for external usage ('camelCase' naming) */
    const AS_GROUP_PRICE = 'groupPrice';
    const AS_STOCK_ITEM = 'stockItem';
    const AS_WRHS_ITEM = 'wrhsItem';

    /** Columns/expressions aliases for external usage ('camelCase' naming) */
    const A_PRICE_GROUP = 'priceGroup';
    const A_PRICE_WRHS = 'priceWrhs';

    /** Bound variables names ('camelCase' naming) */
    const BND_GROUP_ID = 'groupId';
    const BND_PROD_ID = 'prodId';
    const BND_STOCK_ID = 'stockId';

    /** Entities are used in the query */
    const E_GROUP_PRICE = EGroupPrice::ENTITY_NAME;
    const E_STOCK_ITEM = Cfg::ENTITY_MAGE_CATALOGINVENTORY_STOCK_ITEM;
    const E_WRHS = EWrhsItem::ENTITY_NAME;

    public function build(\Magento\Framework\DB\Select $source = null)
    {
        /* this is root query builder (started from SELECT) */
        $result = $this->conn->select();

        /* define tables aliases for internal usage (in this method) */
        $asGroup = self::AS_GROUP_PRICE;
        $asStock = self::AS_STOCK_ITEM;
        $asWrhs = self::AS_WRHS_ITEM;

        /* FROM cataloginventory_stock_item */
        $tbl = $this->resource->getTableName(self::E_STOCK_ITEM);
        $as = $asStock;
        $cols = [];
        $result->from([$as => $tbl], $cols);

        /* LEFT JOIN prxgt_wrhs_stock_item */
        $tbl = $this->resource->getTableName(self::E_WRHS);
        $as = $asWrhs;
        $cols = [
            self::A_PRICE_WRHS => EWrhsItem::ATTR_PRICE
        ];
        $cond = $as . '.' . EWrhsItem::ATTR_STOCK_ITEM_REF . '=' . $asStock . '.' . Cfg::E_CATINV_STOCK_ITEM_A_ITEM_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* LEFT JOIN prxgt_wrhs_group_price */
        $tbl = $this->resource->getTableName(self::E_GROUP_PRICE);
        $as = $asGroup;
        $cols = [
            self::A_PRICE_GROUP => EGroupPrice::ATTR_PRICE
        ];
        $cond = $as . '.' . EGroupPrice::ATTR_STOCK_ITEM_REF . '=' . $asStock . '.' . Cfg::E_CATINV_STOCK_ITEM_A_ITEM_ID;
        $result->joinLeft([$as => $tbl], $cond, $cols);

        /* query tuning */
        $byProdId = "$asStock." . Cfg::E_CATINV_STOCK_ITEM_A_PROD_ID . "=:" . self::BND_PROD_ID;
        $byStockId = "$asStock." . Cfg::E_CATINV_STOCK_ITEM_A_STOCK_ID . "=:" . self::BND_STOCK_ID;
        $byGroupId = "$asGroup." . EGroupPrice::ATTR_CUST_GROUP_REF . "=:" . self::BND_GROUP_ID;
        $result->where("($byProdId) AND ($byStockId) AND ($byGroupId)");

        return $result;
    }
}