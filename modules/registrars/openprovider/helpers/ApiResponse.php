<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

class ApiResponse
{
    public static function success($data = [], $code = 200)
    {
        $result = array_merge($data, ['code' => $code, 'success' => true, 'error' => false]);
        echo json_encode($result);
    }

    public static function error($code = 400, $message = 'Bad request')
    {
        $result = [
            'code' => $code,
            'success' => false,
            'error' => true,
            'message' => $message,
        ];
        echo json_encode($result);
    }
}