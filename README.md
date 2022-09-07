# typo3_monitor_api
typo3_monitor_api is a TYPO3 extension.
Inspired by [zabbix_monitor](https://github.com/WapplerSystems/zabbix_client "Github Repo of zabbix_monitor") extension created by (and thanks to) Sven Wappler.
typo3_monitor_api extension don't uses zabbix at all and it is not compatible with the zabbix system!

### Installation
... like any other TYPO3 extension [extensions.typo3.org](https://extensions.typo3.org/ "TYPO3 Extension Repository")
No TypoScript or PageTs required.

Setup a backend-user (username and password), this user don't need and shouldn't have any rights!!

### Usage
`domain.tld/typo3-monitor-api/v1/{METHOD}`
If required add parameters to the request body as JSON format.

#### Request
Basic Auth - UserName and UserPassword are from a backend user.
format: json (body)
Example for method "GetExtensionVersion":
```json
[
    {
        "extensionKey": "news"
    }
]
```

#### Response
format: json
Every response has at least "status", "value" and "message".
Looks like (`domain.tld/typo3-monitor-api/v1/GetPHPVersion`):
```json
[
    {
        "status": true,
        "value": [
            {
                "version": "7.4.27"
            }
        ],
        "message": ""
    }
]
```

### Methods
get all available methods: `domain.tld/typo3-monitor-api/v1/GetAllowedOperations`
small list:
- CheckPathExists
- GetApplicationContext
- GetDatabaseAnalyzerSummary
- GetDatabaseVersion
- GetDiskSpace
- GetExtensionList
- GetExtensionVersion
- GetFeatureValue
- GetFileSpoolValue
- GetFilesystemChecksum
- GetInsecureExtensionList
- GetLastExtensionListUpdate
- GetLastSchedulerRun
- GetLogResults
- GetOpCacheStatus
- GetOutdatedExtensionList
- GetPHPVersion
- GetProgramVersion
- GetRecord
- GetRecords
- GetSystemInfos
- GetTYPO3Version
- GetTotalLogFilesSize
- HasDeprecationLogEnabled
- HasExtensionUpdate
- HasExtensionUpdateList
- HasFailedSchedulerTask
- HasForbiddenUsers
- HasMissingDefaultMailSettings
- HasRemainingUpdates
- HasSecurityUpdate
- HasStrictSyntaxEnabled
- HasUpdate
- IOperation
- UpdateMinorTypo3

### Extension configuration / settings
- api-access.operations.allowedOperations:
    Allow or disallow specific methods
- api-access.allowedIps:
    Restrict the endpoint to IP Ranges (e.g. 77.6.178.) or specific IPs. Comma separated lists are possible.
- api-access.blockTime:
    Duration in minutes that the respective IP should be blocked (e. g. 5 = 5 minutes locked/blocked since last failed attempt)
- api-access.maxCount:
    how often a request may take place before it is blocked

### TODO:
- add better Authentication
- maybe en- decrypt data

### Troubleshooting
#### .htaccess
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
