<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Domain\Model;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2021 Christian Hackl <web@hauer-heinrich.de>, www.hauer-heinrich.de
 */

/**
 * User
 */
class User extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

    /**
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected string $userName = '';

    /**
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected string $userPassword;

    public function __construct(string $userName, string $userPassword) {
        $this->setUserName($userName);
        $this->setUserPassword($userPassword);
    }

    public function getUserName(): string { return $this->userName; }
    public function setUserName(string $userName): void { $this->userName = $userName; }

    public function getUserPassword(): string { return $this->userPassword; }
    public function setUserPassword(string $userPassword): void { $this->userPassword = $userPassword; }
}
