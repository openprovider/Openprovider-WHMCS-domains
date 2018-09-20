<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;
use Carbon\Carbon;
use OpenProvider\WhmcsDomainAddon\Models\Domain;
use OpenProvider\WhmcsDomainAddon\Models\Invoice;

/**
 * Client controller dispatcher.
 */
class UninvoicedDomainController{

    /**
     * Show an index with all the domains.
     * 
     * @return string
     */
    public function index()
    {   
        $domain = new Domain();
        $domains = $domain->possiblyUninvoicedDomains('paginate');

        $notice = [];

        if(isset($_REQUEST['id']) && $_GET['ignoreStatus'] == 'success')
        {
            try 
            { 
                $domain =  Domain::findOrFail($_REQUEST['id']); 

                $notice =[
                    'notice' => 'Ignored the domain "'.$domain->domain.'" with ID "'.$domain->id.'"',
                    'noticeType' => 'success'
                ];
            }
            catch (\Exception $e)
            {
                // Do nothing.
            }
        }

        return wView('UninvoicedDomain/index', array_merge([
            'domains'  => $domains['items'],
            'pagination' => $domains['pagination'],
            'route' => 'uninvoicedDomainsIndex'
        ], $notice));
    }

    /**
     * Create invoice for a domain.
     *
     * @return string
     */
    public function createInvoiceForm()
    {
        if(!isset($_REQUEST['id']))
            throw new \Exception('NOT FOUND');
        
        $domain =  Domain::findOrFail($_REQUEST['id']);

        return wView('UninvoicedDomain/createInvoiceForm',
        ['domain' => $domain]);
    }

    /**
     * Create invoice for a domain.
     *
     * @return string
     */
    public function createInvoice()
    {
        if(!isset($_REQUEST['id']) || !isset($_POST['description']) || !isset($_POST['amount']))
            throw new \Exception('NOT FOUND');
        
        $domain =  Domain::findOrFail($_REQUEST['id']);
        $difference_invoices = $domain->getExpectedInvoices() - $domain->getFoundInvoices();
        $correctionInvoices = ceil($difference_invoices) - 1;

        $_POST['amount'] = (float) $_POST['amount'];
        
        if(!is_float($_POST['amount']))
        {
            $status = 'error';
            $error  = 'Pricing format is not acceptable.';
            return wView('UninvoicedDomain/createInvoiceForm',
            ['domain' => $domain,
            'error' => $error]);
        }

        // Get the offset date
        $command = 'GetConfigurationValue';
        $postData = array(
            'setting' => 'CreateInvoiceDaysBefore',
        );

        $offset = localAPI($command, $postData)['value'];

        $carbon = new Carbon();
        $carbon->now();
        $currentDate    = $carbon->toDateString();
        $dueDate        = $carbon->addDays($offset)->toDateString();

        $command = 'CreateInvoice';
        $postData = array(
            'userid' => $domain->userid,
            'status'            => 'Unpaid',
            'sendinvoice'       => '1',
            'date'              => $currentDate,
            'duedate'           => $dueDate,
            'itemdescription1'  => $_POST['description'],
            'itemamount1'       => $_POST['amount'] ,
            'itemtaxed1'        => '1',
        );

        $results = localAPI($command, $postData);

        $invoice = new Invoice();
        $invoice = $invoice->find($results['invoiceid']);
        $result  = $invoice->setRelidAndType($domain->id, 'DomainCorrection');
        $result  = $invoice->setCorrectionInvoices($correctionInvoices);


        // @todo Validation

        // @todo 

        return wView('UninvoicedDomain/createInvoiceForm',
        ['domain' => $domain,
        'invoice' => $invoice]);
    }

    /**
     * Ignore a domain.
     *
     * @return string
     */
    public function ignoreDomain()
    {
        if(!isset($_REQUEST['id']))
            throw new \Exception('NOT FOUND');
        
        $domain =  Domain::findOrFail($_REQUEST['id']);
        $difference_invoices = $domain->getExpectedInvoices() - $domain->getFoundInvoices() + $domain->op_correctioninvoices;
        $difference_invoices = $this->round_up($difference_invoices, 4);
        $domain->op_correctioninvoices = $difference_invoices;
        $domain->save();
        
        header("Location: addonmodules.php?module=openprovider&action=uninvoicedDomainsIndex&id=".$domain->id."&ignoreStatus=success");
        exit;
    }
    
    function round_up($number, $precision = 2)
    {
        $precision++;
        $fig = (int) str_pad('1', $precision, '0');
        return (ceil($number * $fig) / $fig);
    }
}
