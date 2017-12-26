<?php
namespace OpenProvider\WhmcsHelpers;
use WHMCS\Database\Capsule;

/**
 * Helper for domain data.
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
class CustomField
{
    public static function getByName($name)
    {
        $customfield = Capsule::table('tblcustomfields')
            ->where('fieldname', 'like', $name .'|%')
            ->first();

        return $customfield;
	}

    /**
     * Get the customfield value from the custom fields array.
     *
     * @param $name
     * @param $fields
     * @return string
     */
    public static function getValueFromCustomFields($name, $fields)
    {
        $customfield_id = self::getByName($name)->id;

        foreach($fields as $field)
        {
            if($field['id'] == $customfield_id)
                return $field['value'];
        }
	}


} // END class Domain