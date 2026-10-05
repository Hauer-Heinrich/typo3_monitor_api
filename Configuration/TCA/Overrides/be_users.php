<?php
defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use HauerHeinrich\Typo3MonitorApi\Api\OperationRegistry;
use HauerHeinrich\Typo3MonitorApi\Authentication\IpAuthenticationProvider;
use HauerHeinrich\Typo3MonitorApi\Authorization\OperationAuthorizationProvider;

call_user_func(function(string $extensionKey) {
    $operationItems = [];
    foreach (OperationRegistry::getOperationNames() as $operationName) {
        $operationItems[] = ['label' => $operationName, 'value' => $operationName];
    }

    // be_users can only be edited by admins. The fields are intentionally NOT added to the
    // "User settings" module, otherwise an API user could grant itself operations.
    ExtensionManagementUtility::addTCAcolumns('be_users', [
        IpAuthenticationProvider::USER_FIELD => [
            'exclude' => true,
            'label' => 'Monitor API: allowed IPs',
            'description' => 'Comma separated list of IPs, wildcards (203.0.113.*), CIDR ranges (203.0.113.0/24, 2001:db8::/32) or prefixes (203.0.113.). '
                . 'Applies in addition to the global "Allowed IPs" setting. Empty = only the global setting applies.',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 40,
            ],
        ],
        OperationAuthorizationProvider::USER_FIELD => [
            'exclude' => true,
            'label' => 'Monitor API: allowed operations',
            'description' => 'Operations this user may call. '
                . 'Only operations that are also enabled in the extension configuration can actually be called.',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => $operationItems,
                'maxitems' => 999,
            ],
        ],
    ]);

    ExtensionManagementUtility::addToAllTCAtypes(
        'be_users',
        '--div--;Monitor API,' . IpAuthenticationProvider::USER_FIELD . ',' . OperationAuthorizationProvider::USER_FIELD
    );
}, 'hh_theme_default');
