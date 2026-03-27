<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;
use WeDevelopCoffee\wPower\Controllers\ViewBaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;


/**
 * Client controller dispatcher.
 */
class BulkDomainTransferController extends ViewBaseController {

    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core, View $view, Validator $validator)
    {
        parent::__construct($core, $view, $validator);
    }

    /**
     * Show page for bulk domain transfers.
     * 
     * @return string
     */
    public function show($params)
    {
        return "Bulk Domain Transfer page loaded successfully.";
    }

}
