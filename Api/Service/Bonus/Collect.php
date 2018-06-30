<?php
/**
 * Authors: Alex Gusev <alex@flancer64.com>
 * Since: 2018
 */

namespace Praxigento\BonusReferral\Api\Service\Bonus;

use Praxigento\BonusReferral\Api\Service\Bonus\Collect\Request as ARequest;
use Praxigento\BonusReferral\Api\Service\Bonus\Collect\Response as AResponse;

/**
 * Collect and paid referral bonus.
 */
interface Collect
{


    /**
     * @param ARequest $request
     * @return AResponse
     * @throws \Exception
     */
    public function exec($request);

}