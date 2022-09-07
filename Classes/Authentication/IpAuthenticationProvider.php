<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authentication;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \HauerHeinrich\Typo3MonitorApi\Utility\Configuration;

class IpAuthenticationProvider
{
    /**
     * checkIpAddress
     * check if given ip-address or ip-range is allowed
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @return void
     */
    static public function checkIpAddress(\TYPO3\CMS\Core\Http\ServerRequest $request) {
        $config = Configuration::getExtConfiguration();

        if(is_array($config) && array_key_exists('allowedIps', $config) && is_string($config['allowedIps']) && $config['allowedIps'] !== '*') {
            if(trim($config['allowedIps']) === '') {
                return true;
            }

            $allowedIps = explode(',', $config['allowedIps']);
            $remoteAddress = $request->getAttribute('normalizedParams')->getRemoteAddress();

            if(empty($remoteAddress)) {
                return false;
            }

            foreach ($allowedIps as $ip) {
                if($ip === $remoteAddress) {
                    return true;
                }

                // Check if its in a valid IP Range
                if(strpos($remoteAddress, $ip) === 0 && preg_match("(:|.)", $ip) === 1 && substr($ip, -1) === ".") {
                    return true;
                }
            }
        }

        return false;
    }
}
