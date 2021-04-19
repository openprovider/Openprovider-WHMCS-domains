<?php

namespace WeDevelopCoffee\wPower\Models;

use Carbon\Carbon;
use WHMCS\Domain\Domain as BaseDomain;
use WHMCS\Database\Capsule;

/**
 * Class Domain
 * @package WeDevelopCoffee\wPower\Models
 */
class Domain extends BaseDomain
{
    public $customQueryObject;

    /**
     * Get the handles for this domain.
     */
    public function handles()
    {
        return $this->belongsToMany('WeDevelopCoffee\wPower\Handles\Models\Handle','wDomain_handle');
    }

    /**
     * Update the offset for the next due date based on the expiry date.
     *
     * @param int $days
     * @return array
     */
    public function updateNextDueDateOffset($days = 3, $max_difference = 40, $registrar = null, $queryBuilder = null)
    {
        if($queryBuilder == null)
            $queryObject = $this;
        else
            $queryObject = $queryBuilder;

        // Select the domains with the wrong offset.
        $domainsQuery = $queryObject->selectRaw('DATE_SUB(`expirydate`, INTERVAL ? DAY) `new_nextduedate`,
DATEDIFF(DATE_SUB(`expirydate`, INTERVAL ? DAY), `nextduedate`) `new_nextduedate_difference`,
tbldomains.*', [$days, $days])
        ->whereRaw('tbldomains.nextduedate !=  DATE_SUB(`expirydate`, INTERVAL ? DAY)', [$days]);

        if($registrar != null)
            $domainsQuery = $domainsQuery->where('registrar', $registrar);

        $domainsQuery = $domainsQuery
            ->havingRaw('new_nextduedate_difference >= ? and new_nextduedate_difference <= ?', [ ($max_difference*-1), $max_difference]);

        $domains = $domainsQuery->get();

        $updated_domains = [];
        
        foreach($domains as $domain)
        {
            $original_nextduedate = $domain->nextduedate;
            $domain->nextduedate = $domain->new_nextduedate;
            $domain->nextinvoicedate = $domain->new_nextduedate;
            $domain->save();

            // Update the invoice item if there was any.
            Capsule::table('tblinvoiceitems')
                ->where('type', 'domain')
                ->where('relid', $domain->id)
                ->where('duedate', $original_nextduedate)
                ->update(['duedate' =>  $domain->nextduedate]);

            $updated_domains[] = [ 'domain' => $domain, 'original_nextduedate' => $original_nextduedate];
        }

        return $updated_domains;
    }

    /**
     * Update the offset for the next due date based on the expiry date.
     *
     * @param int $days_offset
     * @param null $registrar
     * @param null $queryBuilder
     * @return array
     */
    public function updateEmptyNextDueDates($registrar = null, $days_offset = 14, $queryBuilder = null)
    {
        if($queryBuilder == null)
            $queryObject = $this;
        else
            $queryObject = $queryBuilder;

        // Select the domains with the wrong offset
        $domainsQuery = $queryObject->where('nextduedate', '0000-00-00');

        if($registrar != null)
            $domainsQuery = $domainsQuery->where('registrar', $registrar);

        $domains = $domainsQuery->get();

        $updated_domains = [];

        foreach($domains as $domain)
        {
            // Save the original date.
            $original_nextduedate = $domain->nextduedate;

            // Calculate the new date with the offset.
            $new_nextduedate = new Carbon($domain->expirydate);

            // Check if we have a valid timestamp
            if($new_nextduedate->getTimestamp() < 0)
                // We do not have a valid timestamp, let's ignore this domain.
                continue;

            $new_nextduedate->subDays($days_offset);

            // Update with the new date.
            $domain->nextduedate = $new_nextduedate;
            $domain->nextinvoicedate = $new_nextduedate;
            $domain->save();

            $updated_domains[] = [ 'domain' => $domain, 'original_nextduedate' => $original_nextduedate];
        }

        return $updated_domains;
    }
}
