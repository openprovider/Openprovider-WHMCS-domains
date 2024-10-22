<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;
use Carbon\Carbon;
use WeDevelopCoffee\wPower\Controllers\ViewBaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;
use WHMCS\Database\Capsule;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;

/**
 * Client controller dispatcher.
 */
class BulkImportController extends ViewBaseController {

    /**
     * @var object WHMCS\Database\Capsule;
     */
    private $capsule;

    /**
     * @var object Carbon
     */
    private $carbon;

    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core, View $view, Validator $validator,Capsule $capsule, Carbon $carbon)
    {
        parent::__construct($core, $view, $validator);

        $this->capsule = $capsule;
        $this->carbon = $carbon;
    }

    /**
     * Show an index with domain import form.
     * 
     * @return string
     */
    public function index($params)
    {
        $clients     = $this->getClients();
        $client_list = [
            ['value' => '', 'name' => 'Select Client']
        ];

        if($clients['result'] == 'success') {
            foreach ($clients['clients']['client'] as $client) {
                $client_list[] = ['value' => $client['id'], 'name' => $client['firstname'] . ' ' . $client['lastname']];
            }
        }
        
        $payments        = $this->getPaymentMethods();
        $payment_methods = [
            ['value' => '', 'name' => 'Select Payment Method']
        ];
        if($payments['result'] == 'success') {
            foreach ($payments['paymentmethods']['paymentmethod'] as $payment) {
                $payment_methods[] = ['value' => $payment['module'], 'name' => $payment['displayname']];
            }
        }

        $apiUrlImportDomains = Configuration::getApiUrl('bulk-import-adding');
        
        return $this->view('bulk_domain_import/index', 
            [
                'LANG'                => $params['_lang'],
                'client_list'         => $client_list,
                'payment_methods'     => $payment_methods,
                'apiUrlImportDomains' => $apiUrlImportDomains,
            ]
        );
    }

    // Accept Order by WHMCS Internal API
    private function getClients(): array
    {        
        $command  = WHMCSApiActionType::GetClients;
        $postData = array();
        $results  = localAPI($command, $postData);     
        return $results;
    }

    // Get Payment Methods by WHMCS Internal API
    private function getPaymentMethods(): array
    {        
        $command  = WHMCSApiActionType::GetPaymentMethods;
        $postData = array();
        $results  = localAPI($command, $postData);       
        return $results;
    }
}
