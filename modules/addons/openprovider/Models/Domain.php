<?php
namespace OpenProvider\WhmcsDomainAddon\Models;
use WHMCS\Database\Capsule as DB;
// use OpenProvider\WhmcsDomainAddon\Lib\ViewFactory;
// use Illuminate\Pagination\Paginator;
use WeDevelopCoffee\wPower\Core\Paginator;

/**
 * Domain model
 */
class Domain extends \WHMCS\Domain\Domain {
    /**
     * Returns a list of possible uninvoiced domains.
     *
     * @return void
     */
    public function possiblyUninvoicedDomains($paginate = false, $perPage = 25)
    {
        $query = self::prep_findPossiblyUninvoicedDomainsQuery();
        
        $paginator = new Paginator();
        $paginator->setPerPage($perPage)
            ->setQueryAndModel($query, $this);

        $result = $paginator->getResult();
        
        return $result;
    }

    /**
    * getExpectedInvoices
    * 
    * @return integer
    */
    public function getExpectedInvoices ()
    {
        $result = self::prep_findPossiblyUninvoicedDomainsQuery(false)
                ->where($this->table . '.id', $this->id)
                ->get()[0];
    
        return round($result->expectedInvoices, 4);
    }

    /**
    * getFoundInvoices
    * 
    * @return integer
    */
    public function getFoundInvoices ()
    {
        $result = self::prep_findPossiblyUninvoicedDomainsQuery(false)
                ->where( $this->table . '.id', $this->id)
                ->get()[0];
    
        return round($result->actualInvoices, 4);
    }

    /**
    * getInvoiceDifference
    * 
    * @return integer
    */
    public function getInvoiceDifference ()
    {
        $result = self::prep_findPossiblyUninvoicedDomainsQuery(false)
                ->where( $this->table . '.id', $this->id)
                ->get()[0];
    
        return round($result->invoiceDifference, 4);
    }

    /**
     * Return a prepared query to find expected invoiced and unexpected invoices. Filter can be disabled.
     *
     * @param boolean $dontFilterInvoices Default true. Set to false to get all domains.
     * @return object $query
     */
    protected function prep_findPossiblyUninvoicedDomainsQuery($dontFilterInvoices = true, $count = false)
    {

        $columnSelect = [
            'tbldomains.*',
            // The actual invoices based on the found relid's
            DB::raw('(CAST(COALESCE(`tblinvoiceitems`.`actualInvoices`, \'0\') AS DECIMAL(10,4)) + `tbldomains`.`op_correctioninvoices`) AS `actualInvoices`'),

            // Expected invoices based on the registration and expiration date.
            DB::raw('COALESCE(`tdomains`.`expectedInvoices`, \'0\') as expectedInvoices'),

            // Round it up.
            DB::raw('COALESCE(`tdomains`.`ceilExpectedInvoices`, \'0\') as ceilExpectedInvoices'),

            // The difference.
            DB::raw('(expectedInvoices - IFNULL(actualInvoices,0)) as invoiceDifference')
        ];

        if($count == true)
            $columnSelect[] = DB::raw('COUNT(`tbldomains`.`id` as `total`');

        $rawInvoiceItemsQuery = '(SELECT (COUNT(tblinvoiceitems.`id`) + SUM(tblinvoiceitems.`op_correctioninvoices`)) as actualInvoices, relid FROM tblinvoiceitems';
        // When an invoice is being deleted, the invoice item does not get deleted. The following join makes sure that we count the invoiceitems with actual invoices attached.
        $rawInvoiceItemsQuery .= ' inner join tblinvoices ON (tblinvoices.id = tblinvoiceitems.invoiceid) WHERE `type` LIKE \'Domain%\' AND `amount` != \'0.00\' GROUP BY relid) tblinvoiceitems';
        
        $query = self::select($columnSelect)
            ->leftJoin(
                // Count the related invoices for the tbldomains.id
                DB::raw($rawInvoiceItemsQuery), 
                function($join)
                {
                    $join->on('tblinvoiceitems.relid', '=', 'tbldomains.id');
                }
            )
            ->join(
                // Use a 0.10 correction to reduce false-positives.
                DB::raw('(SELECT (TIMESTAMPDIFF(DAY, `tbldomains`.`registrationdate`, `tbldomains`.`expirydate`) / 365 - 0.10) as expectedInvoices,
                            CEIL(TIMESTAMPDIFF(DAY, `tbldomains`.`registrationdate`, `tbldomains`.`expirydate`) / 365 - 0.10) as ceilExpectedInvoices, id
                FROM tbldomains) tdomains'),
                function($join)
                {
                    $join->on('tdomains.id', '=', 'tbldomains.id');
                }
            );
        
        if($dontFilterInvoices != false)
            $query = $query->havingRaw('expectedInvoices > actualInvoices');
        
        return $query;
    }

    
}
