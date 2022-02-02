<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authentication;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\User;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3Fluid\Fluid\ViewHelpers\DebugViewHelper;

class BasicAuthenticationProvider
{
    private $data = [];

    /**
     * ServerRequest
     *
     * @var \TYPO3\CMS\Core\Http\ServerRequest
     */
    protected $request;

    /**
     * Extension configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * isValid
     *
     * @var boolean
     */
    private $isValid = false;

    public function __construct(\TYPO3\CMS\Core\Http\ServerRequest $request, User $user) {
        $this->request = $request;
        // $this->config = Configuration::getExtConfiguration();

        $this->validateRequestUserName($user->getUserName());
        $this->validateRequestUserPassword($user->getUserPassword());
        $this->authUser($user);
    }

    /**
     * validateRequestUserName
     *
     * @param string $name
     * @return void
     */
    public function validateRequestUserName(string $name): void
    {
        if(empty($name)) {
            $this->data[]['message'] = 'Username wrong!';
        }
    }

    /**
     * validateRequestUserPassword
     *
     * @param string $password
     * @return void
     */
    public function validateRequestUserPassword(string $password): void
    {
        if(empty($password)) {
            $this->data[]['message'] = 'Userpassword wrong!';
        }
    }

    /**
     * authUser
     *
     * @param \HauerHeinrich\Typo3MonitorApi\Domain\Model\User $user
     * @return void
     */
    public function authUser(\HauerHeinrich\Typo3MonitorApi\Domain\Model\User $user): void
    {
        if(empty($user->getUserName()) && empty($user->getUserPassword())) {
            $this->data[]['message'] = 'Username or password not set!';
            return;
        }

        // The context, either 'FE' or 'BE'
        $mode = 'BE';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users')->createQueryBuilder();
        $dbUser = $queryBuilder
            ->select('username', 'password')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($user->getUserName()))
            )
            ->execute()->fetch();

        if(empty($dbUser)) {
            $this->data[]['message'] = 'No DB user found!';
            return;
        }

        if(GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->get($dbUser['password'], $mode)
            ->checkPassword($user->getUserPassword(), $dbUser['password'])) {
                $this->isValid = true;
        }
    }

    /**
     * isValid
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * getLogData
     *
     * @return array
     */
    public function getLogData(): array
    {
        return $this->data;
    }
}
