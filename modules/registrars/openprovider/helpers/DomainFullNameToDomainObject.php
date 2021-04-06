<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\API\Domain;

class DomainFullNameToDomainObject
{
    /**
     * @param string $domainFullName
     * @return Domain
     * @throws \Exception
     */
    public static function convert(string $domainFullName): Domain
    {
        $domainArray = explode('.', $domainFullName);
        if (count($domainArray) < 2) {
            throw new \Exception('Domain name has no tld.');
        }

        $domainSld = explode('.', $domainFullName)[0];
        $domainTld = substr(str_replace($domainSld, '', $domainFullName), 1);

        return new Domain([
            'name'      => $domainSld,
            'extension' => $domainTld
        ]);
    }
}
