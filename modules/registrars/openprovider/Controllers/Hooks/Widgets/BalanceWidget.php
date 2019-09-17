<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets;

use OpenProvider\WhmcsRegistrar\src\OpenProvider;

/**
 * Show OP balance
 *
 * @see https://developers.whmcs.com/addon-modules/admin-dashboard-widgets/
 */
class BalanceWidget extends \WHMCS\Module\AbstractWidget
{
    protected $title = 'OpenProvider';
    protected $description = '';
    protected $weight = 150;
    protected $columns = 1;
    protected $cache = false;
    protected $cacheExpiry = 120;
    protected $requiredPermission = '';
    public function getData()
    {
        return array();
    }
    public function generateOutput($data)
    {
        $openprovider = new OpenProvider();
        $balance = $openprovider->api->getResellerBalance()['balance'];

        $statistics = $openprovider->api->getResellerStatistics();

        $domainsTotal = $statistics['domain']['total'];

        if($balance <= 100)
            $balance_css = 'color-red';

        return <<<EOF
<div class="widget-content-padded">
    <div class="row">
    <div class="col-sm-6 bordered-right">
        <div class="item">
            <div class="data $balance_css">€$balance</div>
            <div class="note">Balance</div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="item">
            <div class="data color-orange">$domainsTotal</div>
            <div class="note">Domains</div>
        </div>
    </div>
</div>
</div>
EOF;
    }
}