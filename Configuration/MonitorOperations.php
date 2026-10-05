<?php

/**
 * All operations of the Monitor API.
 *
 * URL:  /typo3-monitor-api/v1/<OperationName>
 *
 * Per operation:
 *   'class'      (required) class implementing \HauerHeinrich\Typo3MonitorApi\Operation\IOperation
 *   'httpMethod' (optional) GET (default), POST or PATCH
 *   'parameters' (optional) allowed parameters: name => type (string, boolean, integer)
 *   'required'   (optional) list of parameter names that must be given
 *
 * Parameters are sent as JSON object in the request body ({"extensionKey": "news"})
 * or as query string (?extensionKey=news).
 *
 * To add an operation: create the class in Classes/Operation/ and add an entry here.
 * Nothing else is needed (the class is fetched from the DI container, see Services.yaml).
 */

use HauerHeinrich\Typo3MonitorApi\Operation;

return [
    'GetAllowedOperations' => [
        'class' => Operation\GetAllowedOperations::class,
    ],
    'GetDiskSpace' => [
        'class' => Operation\GetDiskSpace::class,
        'parameters' => ['format' => 'boolean'],
    ],
    'GetExtensionList' => [
        'class' => Operation\GetExtensionList::class,
        'parameters' => [
            'scopes' => 'string',           // comma separated: system, local, simple-system, simple-local
            'withUpdateInfo' => 'boolean',
        ],
    ],
    'GetExtensionVersion' => [
        'class' => Operation\GetExtensionVersion::class,
        'parameters' => ['extensionKey' => 'string'],
        'required' => ['extensionKey'],
    ],
    'GetFilesystemChecksum' => [
        'class' => Operation\GetFilesystemChecksum::class,
        'parameters' => [
            'path' => 'string',
            'getSingleChecksums' => 'boolean',
        ],
        'required' => ['path'],
    ],
    'GetPHPVersion' => [
        'class' => Operation\GetPHPVersion::class,
    ],
    'GetTYPO3Version' => [
        'class' => Operation\GetTYPO3Version::class,
    ],
    'GetLogResults' => [
        'class' => Operation\GetLogResults::class,
        'parameters' => [
            'filter' => 'string',           // serviceunavailableexception, pagenotfoundexception, otherexceptions, failedlogins, error
            'max' => 'integer',             // default 50, 0 = only return the number of entries
        ],
        'required' => ['filter'],
    ],
    'HasForbiddenUsers' => [
        'class' => Operation\HasForbiddenUsers::class,
        'parameters' => ['usernames' => 'string'],  // comma separated
        'required' => ['usernames'],
    ],
    'HasUpdate' => [
        'class' => Operation\HasUpdate::class,
    ],
    'HasSecurityUpdate' => [
        'class' => Operation\HasSecurityUpdate::class,
    ],
    'GetLastSchedulerRun' => [
        'class' => Operation\GetLastSchedulerRun::class,
        'parameters' => ['format' => 'string'],     // d M Y H:i:s, d M Y, H:i:s, c, r
    ],
    'GetLastExtensionListUpdate' => [
        'class' => Operation\GetLastExtensionListUpdate::class,
        'parameters' => [
            'format' => 'string',           // d M Y H:i:s, d M Y, H:i:s, c, r
            'extensionlist' => 'boolean',   // false = use the scheduler task instead of the extension list
        ],
    ],
    'GetDatabaseVersion' => [
        'class' => Operation\GetDatabaseVersion::class,
    ],
    'GetApplicationContext' => [
        'class' => Operation\GetApplicationContext::class,
    ],
    'GetInsecureExtensionList' => [
        'class' => Operation\GetInsecureExtensionList::class,
        'parameters' => ['scope' => 'string'],      // loaded, existing (default: both)
    ],
    'GetOutdatedExtensionList' => [
        'class' => Operation\GetOutdatedExtensionList::class,
        'parameters' => ['scope' => 'string'],      // loaded, existing (default: both)
    ],
    'GetTotalLogFilesSize' => [
        'class' => Operation\GetTotalLogFilesSize::class,
    ],
    'HasRemainingUpdates' => [
        'class' => Operation\HasRemainingUpdates::class,
    ],
    'HasExtensionUpdate' => [
        'class' => Operation\HasExtensionUpdate::class,
        'parameters' => ['extensionKey' => 'string'],
        'required' => ['extensionKey'],
    ],
    'HasExtensionUpdateList' => [
        'class' => Operation\HasExtensionUpdateList::class,
        'parameters' => ['scope' => 'string'],      // loaded, existing (default: both)
    ],
    'HasDeprecationLogEnabled' => [
        'class' => Operation\HasDeprecationLogEnabled::class,
    ],
    'GetProgramVersion' => [
        'class' => Operation\GetProgramVersion::class,
        'parameters' => ['program' => 'string'],    // openssl, gm, im, optipng, jpegoptim, webp
        'required' => ['program'],
    ],
    'GetFeatureValue' => [
        'class' => Operation\GetFeatureValue::class,
        'parameters' => ['feature' => 'string'],    // cache, context, image, mail, passwordhashing
        'required' => ['feature'],
    ],
    'GetOpCacheStatus' => [
        'class' => Operation\GetOpCacheStatus::class,
    ],
    'GetFileSpoolValue' => [
        'class' => Operation\GetFileSpoolValue::class,
        'parameters' => ['value' => 'string'],      // pending, sending, lag
        'required' => ['value'],
    ],
    'GetDatabaseAnalyzerSummary' => [
        'class' => Operation\GetDatabaseAnalyzerSummary::class,
    ],
    'HasFailedSchedulerTask' => [
        'class' => Operation\HasFailedSchedulerTask::class,
    ],
    'GetSystemInfos' => [
        'class' => Operation\GetSystemInfos::class,
        'parameters' => ['scope' => 'string'],      // all (default)
    ],
    'HasMissingDefaultMailSettings' => [
        'class' => Operation\HasMissingDefaultMailSettings::class,
    ],
    'UpdateMinorTypo3' => [
        'class' => Operation\UpdateMinorTypo3::class,
        'httpMethod' => 'PATCH',
    ],
];
