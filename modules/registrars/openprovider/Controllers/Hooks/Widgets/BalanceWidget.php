<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets;

use OpenProvider\API\APIConfig;
use OpenProvider\API\ApiHelper;

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
    protected $cacheExpiry = 600;
    protected $requiredPermission = '';

    // This is needed because WHMCS includes namespace when referring to the ID in the HTML when the widget
    // is loaded from another namespace.
    // What should be panelBalanceWidget becomes OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets\BalanceWidget
    // which causes issues with refreshing/closing. The getId method allows us to rename the panel.
    public function getId()
    {
        return 'OPBalanceWidget';
    }

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

                    $apiHelper = $launcher->get(ApiHelper::class);
                    $resellerResponse = $apiHelper->getReseller();
                    $balance = $resellerResponse['balance'];
                    $reservedBalance = $resellerResponse['reservedBalance'];
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

                $versionResult = $this->getModuleVersionStatus();

                return [
                    'balance' => $balance,
                    'reservedBalance' => $reservedBalance,
                    'domainsTotal' => $domainsTotal,
                    'html' => $html,
                    'versionResult' => $versionResult
                ];
            }
        }
        return ['error' => "The Openprovider module could not be found, please ensure that you have <a href='https://support.openprovider.eu/hc/en-us/articles/360012991620-Install-and-configure-Openprovider-module-in-WHMCS-8-X'>installed and activated the Openprovider domain registrar module</a>"];
    }

    private function getModuleVersionStatus(): string
    {
        try {
            $url = "https://api.github.com/repos/openprovider/Openprovider-WHMCS-domains/releases/latest";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: MyPHPApp' // GitHub API requires a user agent
            ]);

            $versionResult = "<span style=\"color:#999;\">Version check unavailable</span>";

            $response = curl_exec($ch);
            if ($response === false) {
                $curlError = curl_error($ch);
                curl_close($ch);
                logModuleCall(
                    'Openprovider',
                    'module version retrieval',
                    'cURL error while retrieving Openprovider version',
                    $curlError,
                    null,
                    null
                );
                return $versionResult;
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            // Check if tag_name exists in the response
            if (isset($responseData['tag_name'])) {
                $availableVersion = $responseData['tag_name'];
            } else {
                return $versionResult;
            }

            $installedVersion = explode('-', APIConfig::getModuleVersion())[0];

            // Check if both versions are valid
            if (empty($installedVersion) || empty($availableVersion)) {
                logModuleCall('Openprovider', 'module version retrieval', "Failed to retrieve openprovider version", null, null, null);
                return $versionResult;
            }

            // Compare versions
            if (version_compare($availableVersion, $installedVersion, '>')) {
                $versionResult = "<a href=\"https://github.com/openprovider/Openprovider-WHMCS-domains/releases/tag/$availableVersion\" target=\"_blank\" style=\"color:red;\">Update to version $availableVersion</a>";
            } else {
                $versionResult = "<span style=\"color:green;\">Module is up to date {$availableVersion}</span>";
            }
            return $versionResult;
        } catch (\Throwable $e) {
            logModuleCall(
                'Openprovider',
                'module version retrieval',
                'Failed to retrieve Openprovider version',
                $e->getMessage(),
                null,
                null
            );

            return "<span style=\"color:#999;\">Version check unavailable</span>";
        }
    }


    public function generateOutput($data)
    {
        if (isset($data['error'])) {
            return <<<EOF
                <div class="widget-content-padded">
                            <div style="color:red; font-weight: bold">
                                {$data['error']}
                            </div>
                </div>
            EOF;
        }
        $availableBalance = $data['balance'] - $data['reservedBalance'];
        $balance = number_format((float) $data['balance'], 2);

        if ($data['balance'] <= 100)
            $balance_css = 'text-danger';

        if ($availableBalance <= 100)
            $reservedBalance_css = 'text-danger';

        $availableBalance = number_format((float) $availableBalance, 2);


        return <<<EOF
                    <div class="widget-content-padded">
                        {$data['html']}
                        <div class="row">
                            <div class="col-sm-6 bordered-right">
                                <div class="item">
                                    <div class="data $balance_css" style="display:inline-block;">€$balance</div> <div class="data $reservedBalance_css"  style="display:inline-block;"><small>(€$availableBalance available)</small></div>
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
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="item">
                                    <div class="data">{$data['versionResult']}</div>
                                </div> 
                            </div>
                        </div>
                    </div>
                EOF;
    }
}
