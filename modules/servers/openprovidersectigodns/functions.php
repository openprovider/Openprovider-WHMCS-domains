<?php

/**
 * Example:
 * 'domain.com' => ['domain', 'com']
 *
 * @param string $domain
 * @return array domain array ['domain name', 'domain extension']
 */
function getDomainArrayFromDomain(string $domain): array
{
    $domainArray = explode('.', $domain);
    if (count($domainArray) < 2) {
        throw new \Exception('Domain name has no tld.');
    }

    $domainSld = explode('.', $domain)[0];
    $domainTld = substr(str_replace($domainSld, '', $domain), 1);

    return [
        $domainSld,
        $domainTld
    ];
}
