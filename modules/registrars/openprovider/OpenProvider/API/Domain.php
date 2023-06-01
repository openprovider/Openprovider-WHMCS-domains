<?php

namespace OpenProvider\API;

/**
 * Class Domain
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class Domain extends \OpenProvider\API\AutoloadConstructor
{
    /**
     *
     * @var string
     */
    public $name        =   null;
    
    /**
     *
     * @var string 
     */
    public $extension   =   null;

    protected static $op_status_converter = [
    'ACT' => 'Active',		// ACT	The domain name is active
    'DEL' => 'Grace',		// DEL	The domain name has been deleted, but may still be restored.
        /* Leave FAI out of the array as we need to make the if statement fail in order to log an error. 'FAI' => 'error',	// FAI	The domain name request has failed.*/
    'PEN' => 'Pending',		// PEN	The domain name request is pending further information before the process can continue.
    'REQ' => 'Pending Transfer',		// REQ	The domain name request has been placed, but not yet finished.
    'RRQ' => 'Pending',		// RRQ	The domain name restore has been requested, but not yet completed.
    'SCH' => 'Pending',		// SCH	The domain name is scheduled for transfer in the future.
    ];

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->name . '.' . $this->extension;
    }

    /**
     * Return the OP status to WHMCS status.
     * @param $op_status
     * @return mixed
     */
    public static function convertOpStatusToWhmcs($op_status)
    {
        if(isset(self::$op_status_converter[$op_status]))
            return self::$op_status_converter[$op_status];

        return false;
    }
}
