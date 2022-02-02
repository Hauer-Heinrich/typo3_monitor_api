<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Configuration
{

    const EXTENSION_KEY = 'typo3_monitor_api';

    /**
     * @return array
     */
    public static function getExtConfiguration(): array
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {

            return GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get(self::EXTENSION_KEY);
        }

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::EXTENSION_KEY])) {
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::EXTENSION_KEY]);

            return $extensionConfiguration;

        }

        return [];
    }


    /**
     * Get the whole typoscript array
     * @return array
     */
    public static function getTypoScriptConfiguration(): array
    {
        $configurationManager = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class);

        return $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            self::EXTENSION_KEY
        );
    }

    /**
     * setExtConfiguration
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function setExtConfiguration(string $key, $value): void
    {
        $config = self::getExtConfiguration();
        $config[$key] = $value;
        GeneralUtility::makeInstance(ExtensionConfiguration::class)->set(self::EXTENSION_KEY, '', $config);
    }
}
