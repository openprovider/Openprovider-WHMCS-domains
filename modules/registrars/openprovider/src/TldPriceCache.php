<?php

namespace OpenProvider\WhmcsRegistrar\src;

use Exception;

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
        $filePath = $this->getLocation();

        //Download tld_cache.php if it doesn't exist or if it's older than 24 hours.
        if (file_exists($filePath)) {
            $timePeriodInMinutes = 1440; //Time period = 24 hours
            $lastModifiedTime = filemtime($filePath); // Get the file's last modified time

            $currentTime = time();
            $diffMinutes = ($currentTime - $lastModifiedTime) / 60;
            if ($diffMinutes > $timePeriodInMinutes) {
                // Download tld_cache.php.
                include('BaseCron.php');
                $core = openprovider_registrar_core('system');
                $launch = $core->launch();
                $core->launcher = openprovider_bind_required_classes($core->launcher);
                openprovider_registrar_launch_decorator('DownloadTldPricesCron');
            }
        } else {
            // Download tld_cache.php.
            include('BaseCron.php');
            $core = openprovider_registrar_core('system');
            $launch = $core->launch();
            $core->launcher = openprovider_bind_required_classes($core->launcher);
            openprovider_registrar_launch_decorator('DownloadTldPricesCron');
        }

        if (!@is_file($this->getLocation()))
            return false;

        if (empty($this->get()))
            return false;

        return true;
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
        $file_content = "<?php exit('ACCESS DENIED');?>\n" . $json;

        if (!file_put_contents($this->getLocation(), $file_content))
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
