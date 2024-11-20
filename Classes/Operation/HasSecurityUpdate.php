<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

use \Psr\Http\Message\RequestFactoryInterface;
use \TYPO3\CMS\Core\SingletonInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 *
 */
class HasSecurityUpdate implements IOperation, SingletonInterface
{

    /**
     * @var RequestFactoryInterface
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * @param RequestFactoryInterface $requestFactory
     */
    public function injectRequestFactoryInterface(RequestFactoryInterface $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        $typo3Version = GeneralUtility::makeInstance(\HauerHeinrich\Typo3MonitorApi\Operation\GetTYPO3Version::class)->execute();
        $currentTypo3Version = $typo3Version->getValue();
        if(\is_array($currentTypo3Version)) {
            $currentTypo3Version = $currentTypo3Version[0]['version'];
        }

        $currentMajorVersionArray = \explode('.', $currentTypo3Version);
        if(\is_array($currentMajorVersionArray)) {
            $currentMajorVersion = $currentMajorVersionArray[0];
        }

        $url = 'https://get.typo3.org/v1/api/major/'.$currentMajorVersion.'/release/latest/security';
        $additionalOptions = [
            // Additional headers for this specific request
            'headers' => ['Cache-Control' => 'no-cache'],
            // Additional options, see http://docs.guzzlephp.org/en/latest/request-options.html
            'allow_redirects' => false,
            'cookies' => false,
        ];

        try {
            // Return a PSR-7 compliant response object
            $response = $this->requestFactory->request($url, 'GET', $additionalOptions);
            if ($response->getStatusCode() === 200) {
                if (strpos($response->getHeaderLine('Content-Type'), 'application/json') === 0) {
                    $content = json_decode($response->getBody()->getContents(), true);

                    if(version_compare($currentTypo3Version, $content['version'])) {
                        return new OperationResult(true, [[ 'bool' => true, 'version' => $content['version'] ]], 'Security update available ('.$content['version'].')');
                    }

                    return new OperationResult(true, [[ 'bool' => false ]], 'No security update available.');
                }
            }
        } catch (\Throwable $th) {
            // TODO: log this
            // TODO: return proper error message
            //throw $th;
            return new OperationResult(false, [[ 'exception' => $th ]], 'Error retrieving the patch releases!');
        }

        return new OperationResult(false, [], 'Error retrieving the patch releases!');
    }
}
