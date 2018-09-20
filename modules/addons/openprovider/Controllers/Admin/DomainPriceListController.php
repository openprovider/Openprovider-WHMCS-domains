<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;
use Carbon\Carbon;
use WeDevelopCoffee\OpenProvider_Api\Api;
use WeDevelopCoffee\wPower\Domain\Registrar;
use WeDevelopCoffee\OpenProvider_Api\Request;

/**
* Client controller dispatcher.
*/
class DomainPriceListController{
    
    /**
    * Show an index with all the domains.
    * 
    * @return string
    */
    public function index($status = '')
    {   
        
        return wView('domainPrice/index', ['status' => $status]);
    }
    
    /**
    * Synchronise the local TLD table with WHMCS.
    * 
    * @return string
    */
    public function synchronise ()
    {
        // Get the registrar info
        try
        {
            $configuration = Registrar::get_login_data('openprovider');
        } catch ( \Exception $e)
        {
            return $this->index('moduleDeactivated');
        }
        
        $api = new Api($configuration['OpenproviderAPI']);
        
        $limit = 1000;
        $offset = 0;
        
        do {
            $request = new Request;
            $request->setCommand('searchExtensionRequest')
            ->setAuth(array('username' => $configuration['Username'], 'password' => $configuration['Password']))
            ->setArgs(array(
                'withPrice' => 1,
                'withDescription' => 0,
                'withDiscounts' => 0,
                'status' => array('GAV','ACT'),
                'limit' => $limit,
                'offset' => $offset,
            ));
            $reply = $api->process($request);
            $value = $reply->getValue();
            
            foreach ($value['results'] as $data) {

                // @todo Replace with model.
                echo
                $data['name']."\t".
                $data['prices']['resellerPrice']['product']['currency']."\t".
                $data['prices']['resellerPrice']['product']['price']."\t".
                $data['prices']['transferPrice']['product']['price']."\t".
                $data['prices']['renewPrice']['product']['price']."\t".
                ($data['tradeAvailable'] ? $data['prices']['tradePrice']['product']['price'] : '-')."\t".
                "\t".
                $data['prices']['resellerPrice']['reseller']['currency']."\t".
                $data['prices']['resellerPrice']['reseller']['price']."\t".
                $data['prices']['transferPrice']['reseller']['price']."\t".
                $data['prices']['renewPrice']['reseller']['price']."\t".
                ($data['tradeAvailable'] ? $data['prices']['tradePrice']['reseller']['price'] : '-')."\t".
                "\n";
                
                //print_r($data);
            }
            $offset += $limit;
        } while ($value['total'] > $offset && $offset < 50); // build in a safe max, just in case        
        
        return $this->index('synchronised');
    }
    
}
