<?php

/**
 * Mimic WHMCS globals functions
 *
 * @param $hookPoint
 * @param $priority
 * @param $function
 */
function add_hook($hookPoint, $priority, $function)
{
    // Store in globals for testing purposes.
    $GLOBALS['test']['add_hook'][] = [
        'hookPoint' => $hookPoint,
        'priority' => $priority,
        'function' => $function
    ];
}

/**
 * Mimic WHMCS localAPI
 *
 * @param $command
 * @param $values
 * @param $adminuser
 * @return array
 */
function localAPI($command, $values, $adminuser = null)
{
    return [
        $command,
        $values,
        $adminuser
    ];
}