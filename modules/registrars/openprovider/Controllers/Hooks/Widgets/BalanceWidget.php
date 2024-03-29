<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\XmlApiAdapter;

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

    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var XmlApiAdapter
     */
    private $xmlApiAdapter;

    public function __construct(ApiHelper $apiHelper, XmlApiAdapter $xmlApiAdapter)
    {
        $this->apiHelper = $apiHelper;
        $this->xmlApiAdapter = $xmlApiAdapter;
    }

    public function getData()
    {
        try {
            $resellerResponse = $this->apiHelper->getReseller();
            $balance = $resellerResponse['balance'];

        } catch ( \Exception $e)
        {
            return ['error' => 'The Openprovider module could not be loaded, please check that an API connection can be established and that the login details are correct.'];
        }

        $html = '';
        try {
            // Get the update message.
            $messages = $this->apiHelper->getPromoMessages();
        } catch ( \Exception $e)
        {
            // Do nothing.
        }

        $domainsTotal = $resellerResponse['statistics']['domain']['total'];

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

        return [
            'balance' => $balance,
            'domainsTotal' => $domainsTotal,
            'html' => $html
        ];
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
