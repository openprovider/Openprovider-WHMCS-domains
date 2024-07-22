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
    public function has($params = null)
    {
        if($params != null){
            if (isset($params['test_mode']) && $params['test_mode'] == 'on') {
                $this->downloadTldCacheFromDrive($params);
            } else {
                $this->downloadTldCache($params);
            }
        }         

        if (!@is_file($this->getLocation()))
            return false;

        if (empty($this->get()))
            return false;

        return true;
    }

    /**
     * Download the tld_cache.php file from google drive - For Sandbox Environment.
     */
    protected function downloadTldCacheFromDrive($params): void
    {
        $filePath = $this->getLocation();
        $timePeriodInMinutes = 1440; // 24 hours

        $fileDownloadURL = "http://openprovider-whmcs-lab-dev.nl/downloads/tld_cache.php";
        $fileSavePath = $this->getLocation();

        if (file_exists($filePath)) {
            $lastModifiedTime = filemtime($filePath);
            $currentTime = time();
            $diffMinutes = ($currentTime - $lastModifiedTime) / 60;

            if ($diffMinutes > $timePeriodInMinutes) {
                $api = new \OpenProvider\API\API();
                $api->downloadPHPfile($fileDownloadURL, $fileSavePath);
                return;
            }
            return;
        } else {
            $api = new \OpenProvider\API\API();
            $api->downloadPHPfile($fileDownloadURL, $fileSavePath);
            return;
        }
    }

    /**
     * Download the tld_cache.php file.
     */
    protected function downloadTldCache($params): void
    {
        $filePath = $this->getLocation();
        $timePeriodInMinutes = 1440; // 24 hours

        if (file_exists($filePath)) {
            $lastModifiedTime = filemtime($filePath);
            $currentTime = time();
            $diffMinutes = ($currentTime - $lastModifiedTime) / 60;

            if ($diffMinutes > $timePeriodInMinutes) {
                $api = new \OpenProvider\API\API();
                $api->setParams($params);
                $extensionResponse = $api->getTldsAndPricing();
                $this->write($extensionResponse);
                return;
            }
            return;
        } else {
            $api = new \OpenProvider\API\API();
            $api->setParams($params);
            $extensionResponse = $api->getTldsAndPricing();
            $this->write($extensionResponse);
            return;
        }        
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

        // if (!file_put_contents($this->getLocation(), $file_content))
        //     throw new \Exception('Unable to write to ' . $this->getLocation());

        $fp = fopen($this->getLocation(), 'w');
        if ($fp === false) {
            $errMsg = "ERROR: Error occurred while writing the file. Unable to write to {$this->getLocation()}. Please review file/folder permissions and ensure fopen(), fwrite() functions are allowed";
            logModuleCall('openprovider', 'file_writing_error', null, $errMsg, null, null);
            throw new \Exception($errMsg);
        }

        if (fwrite($fp, $file_content) === false) {
            fclose($fp);
            $errMsg = "ERROR: Error occurred while writing the file. Unable to write to {$this->getLocation()}. Please review file/folder permissions and ensure fopen(), fwrite() functions are allowed";
            logModuleCall('openprovider', 'file_writing_error', null, $errMsg, null, null);
            throw new \Exception($errMsg);
        }

        fclose($fp);

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

