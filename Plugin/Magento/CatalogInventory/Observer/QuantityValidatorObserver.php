<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2021
 */

namespace Praxigento\BonusReferral\Plugin\Magento\CatalogInventory\Observer;

class QuantityValidatorObserver {

    /**
     * Skip quantity validation for referral bonus calculations.
     *
     * @param \Magento\CatalogInventory\Observer\QuantityValidatorObserver $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function aroundExecute(
        \Magento\CatalogInventory\Observer\QuantityValidatorObserver $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        if (!\Praxigento\BonusReferral\Helper\Flag::getRunning()) $proceed($observer);
    }
}
