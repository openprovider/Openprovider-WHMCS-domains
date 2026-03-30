<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiInterface;

/**
 * Class AdminClientDomainsTabController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2025
 */


class AdminClientDomainsTabController
{
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ConfigController constructor.
     */
    public function __construct(ApiInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }
    /**
     * Save additional fields for the Admin Client Domains tab.
     *
     * @param array $vars
     * @return string
     */
    public function save($vars)
    {
        $domainId = (int)($vars['id'] ?? 0);
        if (!$domainId) return;

        // Only for Openprovider domains
        if (!empty($vars['registrar']) && $vars['registrar'] !== 'openprovider') {
            return;
        }

        $values = [];

        if (isset($_REQUEST['op_consent_value'])){
            // Update the consentForPublishing field at registrar
            $values = $this->updateConsentForPublishing($domainId, $_REQUEST);
        }

        // Return values to WHMCS
        if (isset($values['error'])) {
            return $values['error'];
        }
    }

    /**
     * Update consentForPublishing at registrar.
     *
     * @param int   $domainId
     * @param array $requestVars original hook request variables 
     * @return array
     * @throws \Exception on registrar error or WPP conflict
     */
    protected function updateConsentForPublishing(int $domainId, array $requestVars = [])
    {
        $consentForPublishing = $requestVars['op_consent_value'] ?? '0'; // default to '0' if not set consentForPublishing value should be a string

        $opDomainId = $_REQUEST['op_domain_id'] ?? '';

        $values = array();

        try {
            $args = [
                'id' => $opDomainId,
                'additionalData' => [
                    'consentForPublishing' => $consentForPublishing
                ]
            ];
            
            $response = $this->apiClient->call('modifyDomainRequest', $args);

            if (!$response->isSuccess()) {
                throw new \Exception($response->getMessage(), $response->getCode());
            }

            $_SESSION['admin_area_op_domain_info'][$domainId] = [
                'opDomainId' => $opDomainId,
                'consentForPublishing' => (bool)$consentForPublishing
            ];

        } catch (\Exception $e) {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }
}
