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
Parameters are sent as JSON object in the request body or as query string.

#### Request
Basic Auth via the `Authorization` header (HTTPS only) - UserName and UserPassword are from a backend user.
The API never sends a `WWW-Authenticate` challenge, so browsers don't show a login dialog.
```
curl -u 'monitoring:SECRET' https://domain.tld/typo3-monitor-api/v1/GetPHPVersion
```
Parameters as JSON object in the body, e.g. for method "GetExtensionVersion":
```
curl -u 'monitoring:SECRET' https://domain.tld/typo3-monitor-api/v1/GetExtensionVersion -d '{"extensionKey": "news"}' -X GET
```
or as query string (booleans as `1`/`0`/`true`/`false`):
```
curl -u 'monitoring:SECRET' 'https://domain.tld/typo3-monitor-api/v1/GetExtensionVersion?extensionKey=news'
```
The previous format `[{"extensionKey": "news"}]` is still accepted.
Allowed parameters, their types and required parameters per operation: see `Configuration/MonitorOperations.php` or call `GetAllowedOperations`.

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

Errors have the same format (`"status": false` and the reason in `"message"`):

| Status | Meaning |
|--------|---------|
| 200 | Operation executed (check `status` for the operation's own result) |
| 400 | Invalid JSON, unknown parameter, wrong type or missing required parameter |
| 401 | Wrong credentials (only with `debugOutput`, otherwise the site's 404 page) |
| 403 | Operation not enabled globally or not for this user |
| 404 | Unknown operation |
| 405 | Wrong HTTP method (see `Allow` header) |
| 429 | Too many failed logins (only with `debugOutput`) |
| 500 | Exception inside the operation, details in the TYPO3 log |

### Methods
get all available methods: `domain.tld/typo3-monitor-api/v1/GetAllowedOperations`
small list:
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
- HasUpdate
- UpdateMinorTypo3 (HttpMethod: PATCH)

### Adding an operation
1. Create a class in `Classes/Operation/` implementing `IOperation`:
    ```php
    final class GetSomething implements IOperation
    {
        public function __construct(
            private readonly ConnectionPool $connectionPool,   // core services via constructor
        ) {}

        public function execute(array $parameter, ServerRequestInterface $request): OperationResult
        {
            return new OperationResult(true, [[ 'data' => '...' ]]);
        }
    }
    ```
    Only inject TYPO3 core services. Classes of optional extensions (install, extensionmanager, scheduler, ...)
    have to be fetched inside `execute()` with `GeneralUtility::makeInstance()` after checking
    `ExtensionManagementUtility::isLoaded()`, otherwise the DI container can't be built without that extension.
2. Add it to `Configuration/MonitorOperations.php` (HTTP method, parameters, required parameters).
3. Flush the caches. The operation appears in the extension configuration and in the backend user record.

### Permissions per user
1. Create a backend user without any other rights. Use a long, randomly generated password.
2. Backend user record, tab "Monitor API":
    - "allowed operations": tick the operations this user may call.
    - "allowed IPs" (optional): restricts this user to these IPs (in addition to the global setting).
No backend group is needed.

An operation is only callable if it is enabled globally in the extension configuration **and** ticked in the user record.
Users without any API permission are rejected like a wrong password.

### Extension configuration / settings
- api-access.operations.allowedOperations:
    Global upper limit for all users (fail closed). Keep write operations like `UpdateMinorTypo3` disabled unless you really need them.
- api-access.allowedIps (required):
    Comma separated list of IPs, wildcards (`203.0.113.*`), CIDR ranges (`203.0.113.0/24`, `2001:db8::/32`) or prefixes (`203.0.113.`).
    Empty = nobody is allowed, `*` = every IP.
- api-access.allowedUsers:
    Comma separated list of backend usernames that may use the API. Empty = every non-admin backend user.
- api-access.blockTime / api-access.maxCount:
    Brute-force protection: an IP is blocked after `maxCount` failed logins within `blockTime` minutes. `0` disables it.
- api-access.requireHttps:
    Reject requests without TLS (default). Only disable for local development.
- api-access.debugOutput:
    Return JSON error messages instead of the site's 404 page. Do not enable in production.

### Upgrade notes (security release)
- **Allowed IPs:** an empty value now blocks every IP (previously: every IP was allowed). `*` now allows every IP (previously: blocked every IP).
- **Allowed operations:** the checkboxes are now enforced as a global upper limit. Additionally, every API user needs the operations ticked in the user record (see "Permissions per user"). Users without any API permission can't log in anymore.
- **HTTPS** is required by default.
- **No browser login dialog** anymore: 401 responses don't contain a `WWW-Authenticate` header.
- **Admin users** are rejected by default.
- Failed logins are counted per IP, blocked IPs get `429` (debug) or the 404 page.
- **Status codes:** invalid parameters now return `400` (previously `401`), a wrong HTTP method `405`, a disabled operation `403`.
- **Operations:** `execute()` has the new signature `execute(array $parameter, ServerRequestInterface $request)`, operations get their dependencies via constructor. The list of operations moved from `RoutingConfig` to `Configuration/MonitorOperations.php`.

### TODO:
- add better Authentication
- maybe en- decrypt data

### Troubleshooting
#### .htaccess
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
or
RewriteEngine On
RewriteRule .* - [e=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

#### API Request Error
- Unable to call method "getQueryParams" of non-object "request".
  -> mostly this comes from TypoScript Conditions like `[traverse(request.getQueryParams(), 'tx_news_pi1/action') == 'detail']` then check the availablitiy of the "request object" too, like `[request && traverse(request.getQueryParams(), 'tx_news_pi1/action') == 'detail']`!
