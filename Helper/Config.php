<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Helper;

use Magento\Store\Model\ScopeInterface as AScope;

/**
 * Helper to get configuration parameters related to the module.
 */
class Config
{

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return 'true' if Referral Bonus is enabled.
     *
     * @return bool
     */
    public function getBonusEnabled()
    {
        $result = $this->scopeConfig->getValue('praxigento_downline/referral_bonus/enabled', AScope::SCOPE_STORE);
        $result = filter_var($result, FILTER_VALIDATE_BOOLEAN);
        return $result;
    }

    /**
     * Get fixed amount of the bonus fee.
     *
     * @return float
     */
    public function getBonusFeeFixed()
    {
        $result = $this->scopeConfig->getValue('praxigento_downline/referral_bonus/fee_fixed', AScope::SCOPE_STORE);
        $result = filter_var($result, FILTER_VALIDATE_FLOAT);
        if ($result < 0) $result = 0;
        return $result;
    }

    /**
     * Get maximal amount of the referral bonus fee.
     *
     * @return float
     */
    public function getBonusFeeMax()
    {
        $result = $this->scopeConfig->getValue('praxigento_downline/referral_bonus/fee_max', AScope::SCOPE_STORE);
        $result = filter_var($result, FILTER_VALIDATE_FLOAT);
        if ($result < 0) $result = 0;
        return $result;
    }

    /**
     * Get minimal amount of the referral bonus fee.
     *
     * @return float
     */
    public function getBonusFeeMin()
    {
        $result = $this->scopeConfig->getValue('praxigento_downline/referral_bonus/fee_min', AScope::SCOPE_STORE);
        $result = filter_var($result, FILTER_VALIDATE_FLOAT);
        if ($result < 0) $result = 0;
        return $result;
    }

    /**
     * Get percent of the bonus fee (0..1).
     *
     * @return float
     */
    public function getBonusFeePercent()
    {
        $result = $this->scopeConfig->getValue('praxigento_downline/referral_bonus/fee_fixed', AScope::SCOPE_STORE);
        $result = filter_var($result, FILTER_VALIDATE_FLOAT);
        if ($result < 0) $result = 0;
        if ($result > 1) $result = 1;
        return $result;
    }

    /**
     * Days delay before payout will be made.
     *
     * @return int
     */
    public function getBonusPayoutDelay()
    {
        $result = $this->scopeConfig->getValue('praxigento_downline/referral_bonus/delay_days', AScope::SCOPE_STORE);
        if (is_null($result)) $result = 7; // default value for delay
        $result = filter_var($result, FILTER_VALIDATE_INT);
        if ($result < 0) $result = 0;
        return $result;
    }

}