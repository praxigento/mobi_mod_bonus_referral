<?php
/**
 * Create DB schema.
 *
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Setup;

use Praxigento\BonusReferral\Repo\Data\Registry as Registry;

class InstallSchema
    extends \Praxigento\Core\App\Setup\Schema\Base
{

    protected function setup()
    {
        /** Read and parse JSON schema. */
        $pathToFile = __DIR__ . '/../etc/dem.json';
        $pathToNode = '/dBEAR/package/Praxigento/package/Bonus/package/Referral';
        $demPackage = $this->toolDem->readDemPackage($pathToFile, $pathToNode);

        /* Registry */
        $demEntity = $demPackage->get('entity/Registry');
        $this->toolDem->createEntity(Registry::ENTITY_NAME, $demEntity);
    }
}