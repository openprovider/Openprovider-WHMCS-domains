<?php
namespace OpenProvider;

use OpenProvider\WhmcsHelpers\General;

/**
 * Notification
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class Notification
{

    /**
     * @var string the subject of the e-mail
     */
    private $subject;

    /**
     * @var string the e-mail
     */
    private $email;

    public function WPP_contract_unsigned_one_domain($domain)
    {
        $this->subject = "Error: action required before enabling Whois Privacy Protection";
        $email = "<p>Dear Administrator,</p>
        <p>We send you this e-mail as a client or an administrator tried to enable identity protection for the domain {domain}. You did not sign the Whois Privacy Protection contract which is why the system was unable to process the request.</p>
        <p><strong>How to resolve this?</strong></p>
        <ol>
          <li>Navigate to the OpenProvider control panel.</li>
          <li>Click on Account -> Contracts</li>
          <li>Select \"Whois Privacy Protection\".</li>
          <li>When you agree, sign the contract.</li>
          <li>Navigate to WHMCS and search for the domain {domain}.</li>
          <li>If checked*, uncheck \"ID Protection\" and click on save.</li>
          <li>Check \"ID Protection\" and click on save.</li>
        </ol>
        <p><i>* WHMCS only sends the command to enable ID Protection when the checkbox was saved unchecked. Otherwise WHMCS will not send the command to enable identity protection.</i></p>
        <p>When you have setup up domain synchronisation for OpenProvider the identity protection will also get enabled. However, depending on how you configured the synchronisation it may take up to a few days.</p>
        <p>Regards,<br>
          WHMCS powered by OpenProvider</p>
        ";

        $this->email = str_replace('{domain}', $domain, $email);

        return $this;
    }

    public function WPP_contract_unsigned_multiple_domains($domains)
    {
        $this->subject = "Error: action required before enabling Whois Privacy Protection";
        $email = "<p>Dear Administrator,</p>
        <p>We send you this e-mail as a client or an administrator tried to enable identity protection for multiple domains. You did not sign the Whois Privacy Protection contract which is why the system was unable to process the request.</p>
        <p><strong>The domains:</strong></p>
        {domains}
        <p><strong>How to resolve this?</strong></p>
        <ol>
          <li>Navigate to the OpenProvider control panel.</li>
          <li>Click on Account -> Contracts</li>
          <li>Select \"Whois Privacy Protection\".</li>
          <li>When you agree, sign the contract.</li>
          <li>Navigate to WHMCS and search for every listed domain. When you are on a domain management page. Repeast step 6 and 7 for every domain. If the domain synchronisation is setup, this can be done automatically.</li>
          <li>If checked*, uncheck \"ID Protection\" and click on save.</li>
          <li>Check \"ID Protection\" and click on save.</li>
        </ol>
        <p><i>* WHMCS only sends the command to enable ID Protection when the checkbox was saved unchecked. Otherwise WHMCS will not send the command to enable identity protection.</i></p>
        <p>When you have setup up domain synchronisation for OpenProvider the identity protection will also get enabled. However, depending on how you configured the synchronisation it may take up to a few days.</p>
        <p>Regards,<br>
          WHMCS powered by OpenProvider</p>
        ";

        $domains_html = "<ol>\n";

        foreach($domains as $domain)
            $domains_html .= "<li>". $domain . "</li>\n";

        $domains_html .= "</ol>\n";

        $this->email = str_replace('{domains}', $domains_html, $email);

        return $this;
    }
    
    
    /**
     * Send notification to the admins
     */
    public function send_to_admins()
    {
        $command    = 'SendAdminEmail';
        $subject    = 'OpenProvider Activity E-mail';
        $postData   = array(
            'customsubject' => $this->subject,
            'custommessage' => $this->email

        );
        $adminUsername = General::get_admin_user(); // Optional for WHMCS 7.2 and later

        $results = localAPI($command, $postData, $adminUsername);

        if($results['result'] != 'success')
            $this->log('notification_not_sent', ['admin' => $adminUsername, 'subject' => $subject]);
    }

    /**
     * Log the $action with the correct message into WHMCS and prepare for the final report.
     *
     * @param $activity
     * @param $data
     * @param $moduleLog = false
     */
	public static function log($activity, $data, $moduleLog = false)
    {
        /**
         * Log module call.
         *
         * @param string $module The name of the module
         * @param string $action The name of the action being performed
         * @param string|array $requestString The input parameters for the API call
         * @param string|array $responseData The response data from the API call
         * @param string|array $processedData The resulting data after any post processing (eg. json decode, xml decode, etc...)
         * @param array $replaceVars An array of strings for replacement
         */
        $replaceVars = $data['replaceVars'];
        $replaceVars [] = Registrar::get('Username');
        $replaceVars [] = Registrar::get('Password');

        logModuleCall('openprovider', $activity, $data['requestString'], $data['responseData'], $data['processedData'], $replaceVars);
    }


} // END class General