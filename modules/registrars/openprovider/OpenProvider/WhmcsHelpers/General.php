<?php
namespace OpenProvider\WhmcsHelpers;
use	Carbon\Carbon;
use WHMCS\Database\Capsule;

/**
 * General helpers for WHMCS.
 * WhmcsHelper
 *
 * @copyright Copyright (c) WeDevelop.coffee 2018
 */
class General
{
    private static $admin_user;

	/**
	 * Compare the two dates
	 *
	 * @param  string $first_date The next due date in WHMCS
	 * @param  string $second_date The next due date according to the registrar
	 * @param  string $offset_in_days The registrar offset in days. Will be deducted from the actual registrar expiry date.
	 * @param  string $second_date_format *optional*. The date format. Defaults to 'Y-m-d'.
	 * @param  string $second_date_timezone *optional* The timezone of de registrar. Defaults to CEST.
     * @param  integer $allowed_difference_in_days *optional*  If set, this margin will be used.
	 * @return string When the dates matches, 'correct' is returned. If not, the date is returned.
	 **/
	public static function compare_dates($first_date, $second_date, $offset_in_days = '0', $second_date_format = 'Y-m-d', $second_date_timezone = 'Europe/Amsterdam', $allowed_difference_in_days = 0)
	{
		$system_timezone = date_default_timezone_get();

		// Init Carbon for WHMCS next due date
		$first_date = Carbon::createFromFormat('Y-m-d', $first_date, $system_timezone);

		// Init Carbon for the registrar next due date
		$second_date = Carbon::createFromFormat($second_date_format, $second_date, $second_date_timezone);
		
		// Set the offset for the registrar expiry date.
		if($offset_in_days != '0') {
            preg_match_all('!\d+!', $offset_in_days, $offset_in_days);
            $offset_in_days = $offset_in_days[0][0];
            $second_date->subDays($offset_in_days);
        }

		// Convert the registrar timezone to whmcs
		if($first_date->format('Y-m-d') != $second_date->setTimezone($system_timezone)->format('Y-m-d'))
		{
            $difference_in_days = $first_date->diffInDays($second_date, false);

            if($difference_in_days <0)
                $difference_in_days--;

            // Is this withing the margin?
            if($allowed_difference_in_days != 0 && $difference_in_days > $allowed_difference_in_days)
                return 'correct';

			$return = [
			    'date'                 => $second_date->setTimezone($system_timezone)->format('Y-m-d'),
                'difference_in_days'   => $difference_in_days
            ];
			return $return;
		}

		return 'correct';
	}

    /**
     * Get the admin user
     *
     * @return string The $admin
     **/
    public static function get_admin_user()
    {
        if(self::$admin_user != '')
            return self::$admin_user;

        try {
            $admin_results = Capsule::table('tbladmins')
                ->limit(1)
                ->get();
        } catch (\Exception $e) {
            return null;
        }

        self::$admin_user = $admin_results[0]->username;
        return self::$admin_user;
    }
} // END class General