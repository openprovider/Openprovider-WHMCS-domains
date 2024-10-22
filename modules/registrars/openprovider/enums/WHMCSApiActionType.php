<?php


namespace OpenProvider\WhmcsRegistrar\enums;


class WHMCSApiActionType
{
    // Clients
    const AddClient = 'AddClient';
    const GetClients = 'GetClients';
    const UpdateClientDomain = 'UpdateClientDomain';

    // PaymentMethods
    const GetPaymentMethods = 'GetPaymentMethods';

    // Contacts
    const AddContact    = 'AddContact';
    const GetContacts   = 'GetContacts';
    const GetContact    = 'GetContact';
    const DeleteContact = 'DeleteContact';

    // Invoices
    const GetInvoices = 'GetInvoices';
    const CreateInvoice = 'createInvoice';

    // Orders
    const GetOrders   = 'GetOrders';
    const AddOrder    = 'AddOrder';
    const AcceptOrder = 'AcceptOrder';

    // Tlds
    const GetTLDPricing = 'GetTLDPricing';

    // Configuration
    const GetConfigurationValue = 'GetConfigurationValue';
}
