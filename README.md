## Installation
### .htaccess
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

## Usage
Basic Auth - UserName and UserPassword are from a backend user (you should create a own BE-user for the api, with no rights!)

domain.tld/typo3-monitor-api/v1/{METHOD}

#### Request
format: json (body)
Example for method "GetExtensionVersion":
´´´
[
    {
        "extensionKey": "news"
    }
]
´´´

#### Response
format: json
looks like (domain.tld/typo3-monitor-api/v1/GetPHPVersion):
´´´
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
´´´

### Methods
CheckPathExists
GetApplicationContext
GetDatabaseAnalyzerSummary
GetDatabaseVersion
GetDiskSpace
GetExtensionList
GetExtensionVersion
GetFeatureValue
GetFileSpoolValue
GetFilesystemChecksum
GetInsecureExtensionList
GetLastExtensionListUpdate
GetLastSchedulerRun
GetLogResults
GetOpCacheStatus
GetOutdatedExtensionList
GetPHPVersion
GetProgramVersion
GetRecord
GetRecords
GetSystemInfos
GetTYPO3Version
GetTotalLogFilesSize
HasDeprecationLogEnabled
HasExtensionUpdate
HasExtensionUpdateList
HasFailedSchedulerTask
HasForbiddenUsers
HasMissingDefaultMailSettings
HasRemainingUpdates
HasSecurityUpdate
HasStrictSyntaxEnabled
HasUpdate
IOperation
UpdateMinorTypo3

### Options
Extension configuration:
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
