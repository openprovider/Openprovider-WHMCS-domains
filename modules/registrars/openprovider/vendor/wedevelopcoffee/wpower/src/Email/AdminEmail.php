<?php

namespace WeDevelopCoffee\wPower\Email;

/**
 * Class AdminEmail
 * @package WeDevelopCoffee\wPower\Email
 */
class AdminEmail extends BaseEmail
{
    /**
     * @var array
     */
    protected $mergeFields = array();

    /**
     * Send the e-mail to the admin.
     */
    public function send()
    {
        $postData = array(
            'mergefields' => $this->mergeFields
        );

        if($this->messageName != '')
            $postData['messagename'] = $this->messageName;
        else
        {
            $postData['customsubject'] = $this->customSubject;
            $postData['custommessage'] = $this->customMessage;
        }

        $results = $this->API->exec('SendAdminEmail', $postData);

        if($results['result'] == 'error')
            throw new \Exception($results['message']);

        return 'success';
    }

    /**
     * @param array $mergeFields
     * @return AdminEmail
     */
    public function setMergeField($key, $value)
    {
        $this->mergeFields [$key] = $value;
        return $this;
    }
}