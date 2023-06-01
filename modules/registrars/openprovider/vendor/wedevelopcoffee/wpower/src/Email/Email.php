<?php

namespace WeDevelopCoffee\wPower\Email;

/**
 * Class AdminEmail
 * @package WeDevelopCoffee\wPower\Email
 */
class Email extends BaseEmail
{
    /**
     * Related id. For general e-mails the client id. Otherwise, use the related service id (e.g. domain id for domains, service id for services).
     * @var integer
     */
    protected $id;

    /**
     * E-mail type
     * @var string  (‘general’, ‘product’, ‘domain’, ‘invoice’, ‘support’, ‘affiliate’)
     */
    protected $customType = 'general';

    /**
     * @var array
     */
    protected $customVars = array();


    /**
     * Send the e-mail to the user.
     */
    public function send()
    {
        $postData = [
            'id' => $this->id
        ];

        if($this->messageName != '')
            $postData['messagename'] = $this->messageName;
        else
        {
            $postData['customtype'] = $this->customType;
            $postData['customsubject'] = $this->customSubject;
            $postData['custommessage'] = $this->customMessage;
            $postData['customvars'] = $this->customVars;
        }

        $results = $this->API->exec('SendEmail', $postData);

        if($results['result'] == 'error')
            throw new \Exception($results['message']);

        return 'success';
    }

    /**
     * @param int $id
     * @return Email
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $customType
     * @return Email
     */
    public function setCustomType($customType)
    {
        $this->customType = $customType;
        return $this;
    }

    /**
     * @param array $customVars
     * @return Email
     */
    public function setCustomVar($key, $value)
    {
        $this->customVars[$key] = $value;
        return $this;
    }
}