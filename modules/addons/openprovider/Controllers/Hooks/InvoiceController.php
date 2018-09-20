<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Hooks;

use OpenProvider\WhmcsDomainAddon\Models\Invoice;

/**
 * Client controller dispatcher.
 */
class InvoiceController{
    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return string
     */
    public function paid($vars)
    {
        $invoice = new Invoice();
        $invoice = $invoice->find($vars['invoiceid']);
        $result  = $invoice->replaceDomainControllerTypeWith('Domain');
        
        return;
    }
}
