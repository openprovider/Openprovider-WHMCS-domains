<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;
use Carbon\Carbon;
use WeDevelopCoffee\wPower\Controllers\ViewBaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;
use WHMCS\Database\Capsule;
use ZipArchive;
use ZipStream\ZipStream;


/**
 * Client controller dispatcher.
 */
class SupportController extends ViewBaseController {

    /**
     * @var object WHMCS\Database\Capsule;
     */
    private $capsule;

    /**
     * @var object Carbon
     */
    private $carbon;

    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core, View $view, Validator $validator,Capsule $capsule, Carbon $carbon)
    {
        parent::__construct($core, $view, $validator);

        $this->capsule = $capsule;
        $this->carbon = $carbon;
    }

    /**
     * Show an index with all the domains.
     * 
     * @return string
     */
    public function index($params)
    {
        if (!extension_loaded('zip'))
            $zipAvailable = 'no';
        else
            $zipAvailable = 'yes';

        return $this->view('Support/index', ['zipAvailable' => $zipAvailable, 'LANG' => $params['_lang']]);
    }

    /**
     * Create invoice for a domain.
     *
     * @return string
     */
    public function download($params)
    {
        $date = $this->carbon->today()->subDays(30);

        // Fetch activity logs
        $outputActivityLog = $this->getActivityLog($date);

        // Fetch module logs
        $outputModuleLog = $this->getModuleLog($date);
        if (empty($outputModuleLog)) {
            http_response_code(204);
            echo 'The module.log is empty. There is no data to download.';
            exit;
        }

        // Send the file
        $this->generateDownload($outputActivityLog, $outputModuleLog);
    }

    /**
     * @param static $date
     * @param $outputModuleLog
     * @return string
     */
    public function getModuleLog( $date, $outputModuleLog = ""): string
    {
        $moduleLogs = $this->capsule->table('tblmodulelog')
            ->where('date', '>=', $date)
            ->get();

        foreach ($moduleLogs as $log) {
            if (isset($outputModuleLog)) {
                $outputModuleLog .= "\n";
            } else {
                $outputModuleLog = "";
            }

            $outputModuleLog .= <<<EOF
################ START ################
$log->date - $log->module - $log->action

------- START REQUEST -------
$log->request
-- END REQUEST --

------- START RESPONSE -------
$log->response
-- END RESPONSE --

$log->date - $log->module - $log->action
######## END ########

EOF;
            $log->date . " - " . $log->user . " - IP: " . $log->ipaddr . " - " . $log->description;
        }
        return $outputModuleLog;
    }

    /**
     * @param static $date
     * @param $outputActivityLog
     */
    public function getActivityLog( $date, $outputActivityLog = ""): string
    {
        $activityLogs = $this->capsule->table('tblactivitylog')
            ->where('date', '>=', $date)
            ->get();

        foreach ($activityLogs as $log) {
            if (isset($outputActivityLog)) {
                $outputActivityLog .= "\n";
            } else {
                $outputActivityLog = "";
            }

            $outputActivityLog .= $log->date . " - " . $log->user . " - IP: " . $log->ipaddr . " - " . $log->description;
        }

        return $outputActivityLog;
    }

    /**
     * @param string $outputActivityLog
     * @param string $outputModuleLog
     */
    public function generateDownload(string $outputActivityLog, string $outputModuleLog)
    {
        // Prepare the zip
        $filename = date('Ymd_H-i-s') . '_' . $_SERVER['HTTP_HOST'] . '.zip';
        $zip = new zipArchive();

        $tmpFile = tempnam(sys_get_temp_dir(), "zip");
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        // Add the content
        $zip->addFromString('activity.log', $outputActivityLog);
        $zip->addFromString('module.log', $outputModuleLog);
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($tmpFile));
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Send the content
        readfile($tmpFile);

        // Delete the temporarily file
        unlink($tmpFile);

        exit;
    }


}
