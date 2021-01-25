<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\helpers\CSV;

class Report {

    /**
     * @param string $reportFileName
     * @param array $report
     *
     * @return void
     */
    public static function save($file, $report): void
    {
        $data = $report;
        $headers = array_keys(array_shift($report));

        $reportCsv = new CSV($file, FileOpenModeType::CreateAndWrite);
        $reportCsv->setHeaders($headers);
        $reportCsv->open();
        $reportCsv->writeRecords($data);
        $reportCsv->close();

        return;
    }
}
