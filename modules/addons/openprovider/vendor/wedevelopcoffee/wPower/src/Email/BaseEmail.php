<?php

namespace WeDevelopCoffee\wPower\Email;

use WeDevelopCoffee\wPower\Core\API;

/**
 * Class Email
 * @package WeDevelopCoffee\wPower\Email
 */
class BaseEmail
{
    /**
     * @var API
     */
    protected $API;

    /**
     * @var string Message name
     */
    protected $messageName;

    /**
     * @var string Subject
     */
    protected $customSubject;

    /**
     * @var string Body
     */
    protected $customMessage;

    /**
     * BaseEmail constructor.
     * @param API $API
     */
    public function __construct(API $API)
    {
        $this->API = $API;
    }

    /**
     * @param string $messageName
     * @return AdminEmail
     */
    public function setMessageName($messageName)
    {
        $this->messageName = $messageName;
        return $this;
    }

    /**
     * @param string $customSubject
     * @return AdminEmail
     */
    public function setCustomSubject($customSubject)
    {
        $this->customSubject = $customSubject;
        return $this;
    }

    /**
     * @param string $customMessage
     * @return AdminEmail
     */
    public function setCustomMessage($customMessage)
    {
        $this->customMessage = $customMessage;
        return $this;
    }
}