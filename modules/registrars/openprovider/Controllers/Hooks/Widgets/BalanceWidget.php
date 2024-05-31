<?php

namespace WHMCS\Module\Widget;



/**
 * Show OP balance
 * 
 * //Need move this file into /modules/widgets folder
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
        $command = 'GetRegistrars';
        $postData = array();
        $results = localAPI($command, $postData);

        if ($results['status'] == 'success') {
            $isOpenproviderActive = false;
            foreach ($results['registrars'] as $registrar) {
                if ($registrar['module'] === 'openprovider') {
                    $isOpenproviderActive = true;
                    break;
                }
            }

            if ($isOpenproviderActive) {
                try {
                    $core = openprovider_registrar_core();
                    $core->launch();
                    $launcher = openprovider_bind_required_classes($core->launcher);

                    $apiHelper = $launcher->get(\OpenProvider\API\ApiHelper::class);
                    $resellerResponse = $apiHelper->getReseller();
                    $balance = $resellerResponse['balance'];
                } catch (\Exception $e) {
                    return ['error' => 'The Openprovider module could not be loaded, please check that an API connection can be established and that the login details are correct.'];
                }

                $html = '';
                try {
                    // Get the update message.
                    $messages = $apiHelper->getPromoMessages();
                } catch (\Exception $e) {
                    // Do nothing.
                }

                $domainsTotal = $resellerResponse['statistics']['domain']['total'];

                if (isset($messages['results'])) {
                    foreach ($messages['results'] as $message) {
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
        } 
        return ['error' => "The Openprovider module could not be found, please ensure that you have <a href='https://support.openprovider.eu/hc/en-us/articles/360012991620-Install-and-configure-Openprovider-module-in-WHMCS-8-X'>installed and activated the Openprovider domain registrar module</a>"];
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
