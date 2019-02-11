<?php
namespace WeDevelopCoffee\wPower\Domain;
use WeDevelopCoffee\wPower\Core\API;
use WHMCS\Database\Capsule;
use WHMCS\View\Formatter\Price;

/**
 * Fetch all Tld data.
 *
 * @package default
 * @license  WeDevelop.coffee
 **/
class Tld
{
    /**
     * @var API
     */
    private $api;


    private $tlds;
    private $registerTld;
    private $transferTld;
    private $renewTld;
    private $spotlightTld;

    public function __construct(API $api)
    {

        $this->api = $api;
    }


    /**
     * Get the tlds that can be registred.
     *
     * @return mixed
     */
    public function getRegisterTlds()
    {
        $this->getTldPrices();

        return $this->registerTld;
    }

    /**
     * Get the tlds that can be registered.
     *
     * @return mixed
     */
    public function getTransferTlds()
    {
        $this->getTldPrices();

        return $this->registerTld;
    }

    /**
     * Get the tlds that can be renewed.
     *
     * @return mixed
     */
    public function getRenewTld()
    {
        $this->getTldPrices();

        return $this->renewTld;
    }

    /**
     * Get the spotlight Tlds.
     *
     * @return mixed
     */
    public function getSpotlightTlds()
    {
        $this->getTldPrices();

        return $this->spotlightTld;
    }

    /**
     * Retrieves TLDs with their prices from the API.
     */
    private function getTldPrices()
    {
        if(!empty($this->tlds))
            return $this->tlds;

        if(isset($_SESSION['uid']))
            $postData['clientid'] = $_SESSION['uid'];

        $this->tlds = $this->api->exec('GetTLDPricing', $postData);

        $this->processTlds();

        return $this;
    }

    /**
     * Process the TLDs and generate the available TLDs
     */
    private function processTlds()
    {
        $spotlightTld = $this->fetchSpotlightTlds();

        foreach ($this->tlds['pricing'] as $tld => $value) {
            if (isset($value['register']))
                $this->registerTld[] = $tld;

            if (isset($value['transfer']))
                $this->transferTld[] = $tld;

            if (isset($value['renew']))
                $this->renewTld[] = $tld;

            if(isset($spotlightTld['.'.$tld]))
                $this->spotlightTld[] = $this->generateSpotlightTld($tld, $value);
        }

        return $this;
    }

    /**
     * Fetch the Tlds that are positioned in the WHMCS Spotlight
     *
     * @param $adminUsername
     * @return mixed
     */
    private function fetchSpotlightTlds()
    {
        $spotlightTldTmp = explode(',', $this->api->exec('GetConfigurationValue', ['setting' => 'SpotlightTLDs'])['value']);

        $spotlightTld = [];
        global $spotlightTld;
        array_walk($spotlightTldTmp, function($key, $item){
            global $spotlightTld;
            $spotlightTld[$key] = [];
        });

        return $spotlightTld;
    }

    /**
     * @param $tld
     * @param $value
     * @return mixed
     */
    private function generateSpotlightTld($tld, $value)
    {
        $returnValue = [];
        $returnValue['tld'] = '.' . $tld;
        $returnValue['tldNoDots'] = $tld;

        $register = $value['register'];
        $transfer = $value['transfer'];
        $renew = $value['renew'];

        // Get the first available period.
        reset($register);
        $register_key = key($register);
        $returnValue['period'] = $register_key;

        reset($transfer);
        $transfer_key = key($transfer);

        reset($renew);
        $renew_key = key($renew);


        if (isset($value['register']))
            $returnValue['register'] = new Price($value['register'][$register_key]);

        if (isset($value['transfer']))
            $returnValue['transfer'] = new Price($value['transfer'][$transfer_key]);

        if (isset($value['renew']))
            $returnValue['renew'] = new Price($value['renew'][$renew_key]);

        $returnValue['group']; // @todo
        $returnValue['groupDisplayName']; // @todo

        return $returnValue;
    }
}