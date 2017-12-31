<?php
/**
 * Create DB schema.
 *
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusReferral\Setup;

use Praxigento\BonusReferral\Repo\Entity\Data\Registry as Registry;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class InstallSchema
    extends \Praxigento\Core\App\Setup\Schema\Base
{

    protected function _setup()
    {
        /** Read and parse JSON schema. */
        $pathToFile = __DIR__ . '/../etc/dem.json';
        $pathToNode = '/dBEAR/package/Praxigento/package/Bonus/package/Referral';
        $demPackage = $this->_toolDem->readDemPackage($pathToFile, $pathToNode);

        /* Registry */
        $demEntity = $demPackage->get('entity/Registry');
        $this->_toolDem->createEntity(Registry::ENTITY_NAME, $demEntity);
    }
}