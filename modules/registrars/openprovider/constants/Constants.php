<?php

namespace OpenProvider\WhmcsRegistrar\Constants;

use Carbon\Carbon;

class Constants
{
    public static function getAuthTokenExpirationTimeFromNow()
    {
        return  Carbon::now()->addDays(2)->toDateTimeString();
    }
}
