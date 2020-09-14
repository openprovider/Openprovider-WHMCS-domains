<?php
namespace OpenProvider\WhmcsRegistrar\src;

use Exception;
use OpenProvider\API\API;
use WHMCS\Domains\DomainLookup\ResultsList;

/**
 * Class TldPriceCache
 * @package OpenProvider\WhmcsRegistrar
 */
class TldPriceCache
{
    /**
     * Check if the file exists.
     * @return bool
     */
    public function has()
    {
        if(@is_file($this->getLocation()))
            return true;
        else
            return false;
    }

    /**
     * Get the content.
     * @return mixed
     */
    public function get()
    {
        $raw_file = file_get_contents($this->getLocation());
        $json_file = preg_replace('/^.+\n/', '', $raw_file);
        $content = json_decode($json_file, true);

        return $content;
    }

    /**
     * Write the content.
     * @param $content
     */
    public function write($content)
    {
        $json = json_encode($content);
        $file_content = "<?php exit('ACCESS DENIED;?>\n" . $json;

        if(!file_put_contents($this->getLocation(), $file_content))
            throw new \Exception('Unable to write to ' . $this->getLocation());

        return true;
    }

    /**
     * Get the location.
     * @return string
     */
    protected function getLocation()
    {
        $location = $GLOBALS['whmcsAppConfig']->getRootdir() . '/modules/registrars/openprovider/tld_cache.php';
        return $location;
    }
}