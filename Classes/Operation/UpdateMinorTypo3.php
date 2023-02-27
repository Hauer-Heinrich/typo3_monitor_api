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
use \Psr\Http\Message\ServerRequestInterface;

// use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \TYPO3\CMS\Core\SingletonInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Install\Controller\EnvironmentController;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 *
 */
class UpdateMinorTypo3 implements IOperation, SingletonInterface
{
    use \HauerHeinrich\Typo3MonitorApi\Utility\CheckBodyContent;

    /**
     * @var RequestFactoryInterface
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var EnvironmentController
     */
    protected EnvironmentController $environmentController;

    public function __construct(RequestFactoryInterface $requestFactory) {
        $this->requestFactory = $requestFactory;
        $this->environmentController = GeneralUtility::makeInstance(EnvironmentController::class);
        $this->allowedParameter = [];
    }

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult {
        $this->request = $parameter['request'];

        /** @var \TYPO3\CMS\Install\Controller\UpgradeController $upgradeController */
        $upgradeController = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\UpgradeController::class);

        $coreUpdateIsUpdateAvailable = $upgradeController->coreUpdateIsUpdateAvailableAction();

        if($coreUpdateIsUpdateAvailable->getStatusCode() === 200) {
            $jsonResponse = json_decode($coreUpdateIsUpdateAvailable->getBody()->getContents(), true);

            if($jsonResponse['success']) {
                if($jsonResponse['success'] && ($jsonResponse['action']['action'] === 'updateRegular' || $jsonResponse['status'][0]['title'] === 'Update available!') ) {
                    $folderStructureStatus = $this->environmentController->folderStructureGetStatusAction($this->request);
                    $folderStructureStatusContent = json_decode($folderStructureStatus->getBody()->getContents(), true);

                    if(!empty($folderStructureStatusContent['errorStatus'])) {
                        try {
                            $this->environmentController->folderStructureFixAction();
                        } catch (\Throwable $th) {
                            return new OperationResult(false, [[ 'exception' => $th ]]);
                        }
                    }

                    $this->request = $this->request->withQueryParams([
                        'install' => [
                            'type' => 'regular'
                        ]
                    ]);

                    $checkPreConditions = $this->checkUpdateResponse($upgradeController->coreUpdateCheckPreConditionsAction($this->request));
                    if($checkPreConditions['success']) {
                        $coreUpdateDownload = $this->checkUpdateResponse($upgradeController->coreUpdateDownloadAction($this->request));
                        if($coreUpdateDownload['success']) {
                            $coreUpdateVerifyChecksum = $this->checkUpdateResponse($upgradeController->coreUpdateVerifyChecksumAction($this->request));
                            if($coreUpdateVerifyChecksum['success']) {
                                $coreUpdateUnpack = $this->checkUpdateResponse($upgradeController->coreUpdateUnpackAction($this->request));
                                if($coreUpdateUnpack['success']) {
                                    $coreUpdateMove = $this->checkUpdateResponse($upgradeController->coreUpdateMoveAction($this->request));
                                    if($coreUpdateMove['success']) {
                                        $typo3SourcePath = readlink('typo3_src');
                                        $typo3Typo3Path = readlink('typo3');
                                        $typo3IndexPath = readlink('index.php');

                                        $coreUpdateActivate = $this->checkUpdateResponse($upgradeController->coreUpdateActivateAction($this->request));
                                        if($coreUpdateActivate['success']) {

                                            if(!empty($this->request->getAttribute('normalizedParams'))) {
                                                $requestHost = $this->request->getAttribute('normalizedParams')->getRequestHost();
                                                if($this->checkWebsiteStatusCode($requestHost)) {
                                                    return new OperationResult(true, [true], 'Website returns statusCode 200, it should be fine!');
                                                }

                                                $hasUpdateClass = GeneralUtility::makeInstance(\HauerHeinrich\Typo3MonitorApi\Operation\HasUpdate::class);
                                                $hasUpdate = $hasUpdateClass->execute();
                                                $hasUpdateValue = $hasUpdate->getValue();

                                                if(!isset($hasUpdateValue[0]['version'])) {
                                                    return new OperationResult(true, [true], "Can't get new versions number! (hasUpdateValue)");
                                                }

                                                $typo3SourceDirectory = dirname($typo3SourcePath);
                                                $newTypo3SourcePath = $typo3SourceDirectory . '/typo3_src-' . $hasUpdateValue[0]['version'];

                                                if($this->createTypo3Symlinks($newTypo3SourcePath, $typo3Typo3Path, $typo3IndexPath)) {
                                                    if($this->checkWebsiteStatusCode($requestHost)) {
                                                        return new OperationResult(true, [true], 'Website returns statusCode 200, it should be fine!');
                                                    }

                                                    return new OperationResult(true, [], 'coreUpdateActivate failed! CheckWebsiteStatusCode after symlinks broken!');
                                                } else {
                                                    return new OperationResult(true, [], 'coreUpdateActivate failed! Can not create symlinks!');
                                                }
                                            }

                                            return new OperationResult(true, [], 'coreUpdateActivate failed!');
                                        }

                                        return new OperationResult(true, $coreUpdateActivate);
                                    }

                                    return new OperationResult(true, $coreUpdateMove);
                                }

                                return new OperationResult(true, $coreUpdateUnpack);
                            }

                            return new OperationResult(true, $coreUpdateVerifyChecksum);
                        }

                        return new OperationResult(true, $coreUpdateDownload);
                    }

                    return new OperationResult(true, [], 'Can\'t check pre conditions (no or wrong response)! Request status code: '. $checkPreConditions->getStatusCode());
                }

                return new OperationResult(true, $jsonResponse);
            }

            return new OperationResult(true, [false]);
        }

        return new OperationResult(true, [false]);
    }

    /**
     * checkUpdateResponse
     *
     * @param \TYPO3\CMS\Core\Http\JsonResponse $response
     * @return array
     */
    public function checkUpdateResponse(\TYPO3\CMS\Core\Http\JsonResponse $response): array {
        if($response->getStatusCode() === 200) {
            return json_decode($response->getBody()->getContents(), true);
        }

        return [
            'success' => false,
            'status' => $response->getStatusCode()
        ];
    }

    /**
     * checkWebsiteStatusCode
     * checks if provided url returns statusCode 200
     *
     * @param string $url Website url
     * @return bool
     */
    public function checkWebsiteStatusCode(string $url): bool {
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
            // Get the content as a string on a successful request
            if ($response->getStatusCode() === 200) {
                if (strpos($response->getHeaderLine('Content-Type'), 'text/html') === 0) {
                    return true;
                }
            }
        } catch (\Throwable $th) {
            // TODO: log this
            //throw $th;
            return false;
        }

        return false;
    }

    /**
     * createTypo3Symlinks
     *
     * @param string $sourcePath
     * @param string $typo3Path
     * @param string $indexPath
     * @return bool
     */
    public function createTypo3Symlinks(string $sourcePath, string $typo3Path = 'typo3_src/typo3/', string $indexPath = 'typo3_src/index.php') {
        unlink('typo3_src');
        unlink('index.php');
        unlink('typo3');

        if(symlink($sourcePath, 'typo3_src')) {
            if(symlink($typo3Path, 'typo3')) {
                if(symlink($indexPath, 'index.php')) {
                    return true;
                }
            }
        }

        return false;
    }
}
