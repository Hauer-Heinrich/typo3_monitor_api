<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

trait CheckBodyContent {

    public $returnValue = [];

    public $errors = [];

    public $allowedParameter = [];

    protected $bodyContentArray = [];

    public function checkBodyContent(\TYPO3\CMS\Core\Http\ServerRequest $request): bool {
        // Check if given body is empty or valid json string
        $body = $request->getBody();
        $bodyContent = $body->getContents();
        if(empty($bodyContent) || $this->isJson($bodyContent)) {
            $this->bodyContentArray = json_decode($bodyContent, null, 512, JSON_OBJECT_AS_ARRAY);
            foreach($this->bodyContentArray as $contentKey => $contentValue) {
                if(!array_key_exists($contentKey, $this->allowedParameter)) {
                    $this->errors['bodyContent'] = 'bodyContent not valid!';
                    return false;
                }

                if(gettype($contentValue) !== $this->allowedParameter[$contentKey]) {
                    $this->errors['bodyContent'] = 'bodyContent not valid!';
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if given string is valid json
     * @param string $string
     * @return bool
     */
    public function isJson(string $string): bool {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
