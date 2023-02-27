<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

trait CheckBodyContent {

    public $returnValue = [];

    public $errors = [];

    public $allowedParameter = [];

    protected $bodyContentArray = [];

    /**
     * Check if given string is valid json
     * @param string $string
     * @return bool
     */
    public function isJson(string $string): bool {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * getArrayFromBodyJson
     * checks if request body is json, if true then return json as array
     * else return empty array
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @return array
     */
    public function getArrayFromBodyJson(\TYPO3\CMS\Core\Http\ServerRequest $request): array {
        $body = $request->getBody();
        $bodyContent = $body->getContents();

        if($this->isJson($bodyContent)) {
            return json_decode($bodyContent, null, 512, JSON_OBJECT_AS_ARRAY);
        }

        // TODO: log this no valid json given
        return [];
    }
}
