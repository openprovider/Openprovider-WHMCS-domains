<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\API\Domain;

class DomainFullNameToDomainObject
{
    /**
     * @param string $domainFullName
     * @return Domain
     */
    public static function convert(string $domainFullName): Domain
    {
        $domain_sld = explode('.', $domainFullName)[0];
        $domain_tld = substr(str_replace($domain_sld, '', $domainFullName), 1);

        return new Domain(array(
            'name'      => $domain_sld,
            'extension' => $domain_tld
        ));
    }
}
