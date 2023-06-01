<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

class Debug
{
    const SEVERITY_DEBUG = 'DEBUG';
    const SEVERITY_ERROR = 'ERROR';
    const SEVERITY_INFO = 'INFO';
    const SEVERITY_WARNING = 'WARN';

    const MODULE_IMPORT_CUSTOMERS = 'CUSTOMERS';
    const MODULE_IMPORT_INVOICES  = 'INVOICES';
    const MODULE_IMPORT_CONTACTS  = 'CONTACTS';
    const MODULE_IMPORT_DOMAINS   = 'DOMAINS';
    const MODULE_IMPORT_SSL   = 'SSL';

    const SCRIPT_STARTED = "\n====================Script started====================\n";
    const SCRIPT_FINISHED = "\n====================Script finished====================\n";
    const PREPARING_DATA = "\nPreparing data for import\n";
    const PREPARATION_DONE = "\nData prepared\n";
    const IMPORTING_OBJECTS = "\nImporting objects to WHMCS\n";
    const DELETING_OBJECTS = "\nDeleting objects from WHMCS\n";
    const LIMIT_REACHED = "\nImport limit reached\n";
    const OPERATION_FINISHED = "\nOperation finished. Preparing report\n";
    const DONE = "\nDone\n";

    static function concat(): string
    {
        $args = func_get_args();
        $ret  = '';
        foreach ($args as $t) {
            $ret .= !is_scalar($t) ? var_export($t, true) : $t;
        }
        return $ret;
    }

    static function progressBar($done, $total) {
        $perc = floor(($done / $total) * 100);
        $left = 100 - $perc;

        return sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total", "", "");
    }

    static function formatMessage($severity, $module, $message, $args = []): string
    {
        $argsText = !empty($args) ? (' #' . json_encode($args) . '#') : '';
        return '[' . $severity . ':' . $module . ']: ' . $message . $argsText;
    }

    static function errorLog($msg, $module, $type = Debug::SEVERITY_ERROR)
    {
        error_log(
            Debug::formatMessage($type, $module, $msg)
        );
    }
}
