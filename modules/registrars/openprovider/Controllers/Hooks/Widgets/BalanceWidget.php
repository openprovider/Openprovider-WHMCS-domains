<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets;

use OpenProvider\OpenProvider;
use Punic\Exception;

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
    protected $cache = true;
    protected $cacheExpiry = 120;
    protected $requiredPermission = '';

    public function getData()
    {
        try {
            $openprovider = new OpenProvider();
            $balance = $openprovider->api->getResellerBalance()['balance'];

            $statistics = $openprovider->api->getResellerStatistics();

        } catch ( \Exception $e)
        {
            return ['error' => 'The Openprovider module could not be loaded, please check that an API connection can be established and that the login details are correct.'];
        }

        $html = '';
        try {
            // Get the update message.
            $messages = $openprovider->api->getUpdateMessage();
        } catch ( \Exception $e)
        {
            // Do nothing.
        }

        $domainsTotal = $statistics['domain']['total'];

        if(isset($messages['results']))
        {
            foreach($messages['results'] as $message)
            {
                $html .= "<div class=\"row\">
    <div class=\"col-sm-12\">" . $message['html'] . "
    </div>
</div>";
            }
        }

        $returnData = [
            'balance' => $balance,
            'domainsTotal' => $domainsTotal,
            'html' => $html
        ];

        return $returnData;
    }

    public function generateOutput($data)
    {
        if(isset($data['error']))
        {
            return <<<EOF
<div class="widget-content-padded">
            <div style="color:red; font-weight: bold">
                {$data['error']}
            </div>
</div>
EOF;
        }

        if($data['balance'] <= 100)
            $balance_css = 'color-red';

        return <<<EOF
<div class="widget-content-padded">
    {$data['html']}
    <div class="row">
        <div class="col-sm-6 bordered-right">
            <div class="item">
                <div class="data $balance_css">€{$data['balance']}</div>
                <div class="note">Balance</div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="item">
                <div class="data color-orange">{$data['domainsTotal']}</div>
                <div class="note">Domains</div>
            </div>
        </div>
    </div>
</div>
EOF;
    }
}