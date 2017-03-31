<?php
namespace OpenProvider\WhmcsHelpers;
use	Carbon\Carbon;

/**
 * General helpers for WHMCS.
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
class General
{
	/**
	 * Compare the two dates
	 *
	 * @param  string $first_date The next due date in WHMCS
	 * @param  string $second_date The next due date according to the registrar
	 * @param  string $offset_in_days The registrar offset in days. Will be deducted from the actual registrar expiry date.
	 * @param  string $second_date_format *optional*. The date format. Defaults to 'Y-m-d'.
	 * @param  string $second_date_timezone *optional* The timezone of de registrar. Defaults to CEST.
	 * @return string When the dates matches, 'correct' is returned. If not, the date is returned.
	 **/
	public static function compare_dates($first_date, $second_date, $offset_in_days = '0', $second_date_format = 'Y-m-d', $second_date_timezone = 'CEST')
	{
		$system_timezone = date_default_timezone_get();

		// Init Carbon for WHMCS next due date
		$first_date = Carbon::createFromFormat('Y-m-d', $first_date, $system_timezone);

		// Init Carbon for the registrar next due date
		$second_date = Carbon::createFromFormat($second_date_format, $second_date, $second_date_timezone);
		
		// Set the offset for the registrar expiry date.
		if($offset_in_days != '0')
			$second_date->subDays($offset_in_days);

		// Convert the registrar timezone to whmcs
		if(!$first_date->isSameDay($second_date))
		{
			$second_date->setTimezone($system_timezone);
			return $second_date->toDateString();
		}

		return 'correct';
	}

} // END class General