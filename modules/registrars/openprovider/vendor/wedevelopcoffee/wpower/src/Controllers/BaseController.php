<?php
namespace WeDevelopCoffee\wPower\Controllers;

use phpDocumentor\Reflection\Types\Boolean;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;


/**
 * Controller dispatcher
 */
class BaseController
{
    /**
     * @var Core
     */
    protected $core;


    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
    }

}
