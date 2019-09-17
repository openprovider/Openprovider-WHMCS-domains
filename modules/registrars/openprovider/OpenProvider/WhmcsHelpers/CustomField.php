<?php
namespace OpenProvider\WhmcsHelpers;
use WHMCS\Database\Capsule;

/**
 * Helper for domain data.
 * WhmcsHelper
 *
 * @copyright Copyright (c) WeDevelop.coffee 2018
 */
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