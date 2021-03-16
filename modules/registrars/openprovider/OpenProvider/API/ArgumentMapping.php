<?php

namespace OpenProvider\API;

class ArgumentMapping
{
    /**
     * @param array $existedArgs
     * @param array $requestArgs
     * @return array
     */
    public static function mapExistedWithNotExisted(array $existedArgs = [], array $requestArgs = []): array
    {
         $mappedArguments = [];
         foreach ($requestArgs as $argName) {
             if (array_key_exists($argName, $existedArgs)) {
                 $mappedArguments[$argName] = $existedArgs[$argName];
                 continue;
             }

             $mappedArguments[$argName] = null;
         }

         return $mappedArguments;
    }
}
