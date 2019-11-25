<?php
namespace WeDevelopCoffee\wPower\Controllers;

use phpDocumentor\Reflection\Types\Boolean;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;


/**
 * Controller dispatcher
 */
class ViewClientBaseController extends ViewBaseController
{
    /**
     * @var ClientArea
     */
    protected $clientArea;

    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core, View $view, Validator $validator, ClientArea $clientArea)
    {
        $this->clientArea = $clientArea;
        parent::__construct($core, $view, $validator);
    }

    /**
     * Require a login
     *
     * @return array
     */
    protected function requireLogin()
    {
        if(!$this->clientArea->isLoggedIn())
        {
            return array(
                'requirelogin' => true, # accepts true/false
                'forcessl' => true, # accepts true/false
            );
        }

        return false;
    }

    /**
     * Get the client data
     *
     * @return array
     */
    protected function getClientData()
    {
        $client = Capsule::table('tblclients')
            ->where('id', '=', $this->clientArea->getUserID())->first();
        return $client;
    }
}
