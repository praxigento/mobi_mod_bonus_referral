<?php
/**
 * Flag to indicate referral bonus calculation to prevent stock validation.
 *
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2021
 */

namespace Praxigento\BonusReferral\Helper;


class Flag {
    /**
     * @var bool indicates that referral bonus calculation is running.
     */
    private static $running = false;

    public static function getRunning(): bool {
        return self::$running;
    }

    public static function setRunning($data) {
        self::$running = $data;
    }
}
