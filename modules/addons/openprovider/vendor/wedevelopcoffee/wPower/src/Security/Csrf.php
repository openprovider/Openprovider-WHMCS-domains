<?php

namespace WeDevelopCoffee\wPower\Security;

use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Security\Exceptions\CsrfTokenException;

/**
 * Class Csrf
 * @package WeDevelopCoffee\wPower\Security
 */
class Csrf
{
    private $sessionTokenKey = 'wToken';
    /**
     * @var Core
     */
    private $core;

    public function __construct(Core $core)
    {
        if($core->isCli() == false && (session_id() == '' || !isset($_SESSION))) {
            // session isn't started
            session_start();
        }
        $this->core = $core;
    }

    /**
     * Generate the Csrf token.
     *
     * @return string
     * @throws \Exception
     */
    public function generateCsrf()
    {
        if(PHP_MAJOR_VERSION >= 7)
        {
            $csrfToken = $this->generateCsrfPhp7();
        }
        else
        {
            $csrfToken = $this->generateCsrfPhp5();
        }

        $_SESSION[$this->sessionTokenKey] = $csrfToken;

        return $csrfToken;
    }

    /**
     * Verify the token. Always clears the token.
     *
     * @param $inputToken
     * @return bool true on success, false on error.
     */
    public function verifyToken($inputToken)
    {
        $sessionToken = $this->getToken();

        $this->clearToken();

        if(hash_equals($sessionToken, $inputToken))
            return true;

        throw new CsrfTokenException();
    }

    /**
     * Generate a CSRF token for PHP5.
     * 
     * @return string
     */
    private function generateCsrfPhp5()
    {
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
        } else {
            return bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    /**
     * Generate a CSRF token in PHP7.
     *
     * @return string
     * @throws \Exception
     */
    private function generateCsrfPhp7()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Clear the token.
     */
    private function clearToken()
    {
        $_SESSION[$this->sessionTokenKey] = '';
    }

    /**
     * Get the token
     *
     * @return mixed
     */
    public function getToken()
    {
        return $_SESSION[$this->sessionTokenKey];
    }
}