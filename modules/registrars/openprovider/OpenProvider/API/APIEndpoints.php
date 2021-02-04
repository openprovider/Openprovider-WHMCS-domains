<?php


namespace OpenProvider\API;


class APIEndpoints
{
    // AUTH
    const AUTH_LOGIN = '/auth/login';

    // DOMAINS
    const DOMAINS  = '/domains';
    const DOMAINS_ID  = '/domains/{id}';
    const DOMAINS_CHECK = '/domains/check';
    const DOMAINS_TRADE = '/domains/trade';
    const DOMAINS_TRANSFER = '/domains/transfer';
    const DOMAINS_ID_LASTOPERATION_RESTART = '/domains/{id}/last-operation/restart';
    const DOMAINS_ID_RENEW = '/domains/{id}/renew';
    const DOMAINS_ID_RESTORE = '/domains/{id}/restore';
    const DOMAINS_ID_TRANSFER_APPROVE = '/domains/{id}/transfer/approve';
    const DOMAINS_ID_TRANSFER_SENDFOAL = '/domains/{id}/transfer/send-foa1';

    // Customers
    const CUSTOMERS = '/customers';
    const CUSTOMERS_HANDLE = '/customers/{handle}';
    const CUSTOMERS_VERIFICATIONS_EMAILS_DOMAINS = '/customers/verifications/emails/domains';
    const CUSTOMERS_VERIFICATIONS_EMAILS_RESTART = '/customers/verifications/emails/restart';
    const CUSTOMERS_VERIFICATIONS_EMAILS_START = '/customers/verifications/emails/start';

    // Contacts
    const CONTACTS = '/contacts';
    const CONTACTS_ID = '/contacts/{id}';

    // DNS
    const DNS_ZONES = '/dns/zones';
    const DNS_ZONES_NAME = '/dns/zones/{name}';
    const DNS_ZONES_NAME_RECORDS = '/dns/zones/{name}/records';
    const DNS_NAMESERVERS = '/dns/nameservers';
    const DNS_NAMESERVERS_NAME = '/dns/nameservers/{name}';

    // Tags
    const TAGS = '/tags';

    // Tlds
    const TLDS = '/tlds';

    // Resellers
    const RESELLERS = '/resellers';
}