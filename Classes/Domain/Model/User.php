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
class User extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * userName
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $userName = '';

    /**
     * userPassword
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $userPassword;

    public function __construct($userName, $userPassword) {
        $this->setUserName($userName);
        $this->setUserPassword($userPassword);
    }

    /**
     * Returns the userName
     *
     * @return string $userName
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Sets the userName
     *
     * @param string $userName
     * @return void
     */
    public function setUserName(string $userName)
    {
        $this->userName = $userName;
    }

    /**
     * Returns the userPassword
     *
     * @return string $userPassword
     */
    public function getUserPassword()
    {
        return $this->userPassword;
    }

    /**
     * Sets the userPassword
     *
     * @param string $userPassword
     * @return void
     */
    public function setUserPassword(string $userPassword)
    {
        $this->userPassword = $userPassword;
    }
}
