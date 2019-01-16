<?php
namespace OpenProvider\WhmcsDomainAddon\Models;
use Illuminate\Pagination\LengthAwarePaginator;
use WHMCS\Database\Capsule as DB;
use OpenProvider\WhmcsDomainAddon\Lib\ViewFactory;
use Illuminate\Pagination\Paginator;
/**
 * Domain model
 */
class Invoice extends \WHMCS\Billing\Invoice {

    /**
    * Set the Relid to the service id and type to the service type.
    *
    * $id
    * @return void
    */
    public function setRelidAndType ($relid, $type)
    {
        // Find the invoice items
        $items = $this->items()->get();

        foreach($items as $item)
        {
            $item->relid    = $relid;
            $item->type     = $type;
            $item->save();
        }

        return;
    }

    /**
    * Set the type of the invoiceitems if the type was DomainCorrection.
    * @param string $type
    * @return void
    */
    public function replaceDomainControllerTypeWith ($type)
    {
        // Find the invoice items
        $items = $this->items()->get();

        foreach($items as $item)
        {
            if($item->type == 'DomainCorrection')
            {
                $item->type     = $type;
                $item->save();
            }
        }

        return;
    }

    /**
    * Set the number of correction invoices
    * 
    * Will set the additional number of invoices on top of the current invoice.
    * 
    * $id
    * @return void
    */
    public function setCorrectionInvoices ($relid, $type)
    {
        // Find the invoice items
        $items = $this->items()->get();

        foreach($items as $item)
        {
            $item->op_correctioninvoices    = $relid;
            $item->save();
        }

        return;
    }
}
