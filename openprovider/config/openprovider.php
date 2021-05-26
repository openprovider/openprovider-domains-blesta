<?php

// Domain fields
Configure::set('OpenProvider.domain_fields', [
    'domain' => [
        'label' => Language::_('OpenProvider.domain.domain', true),
        'type' => 'text'
    ],
]);

// Nameserver fields
Configure::set('OpenProvider.nameserver_fields', [
    'ns1' => [
        'label' => Language::_('OpenProvider.nameserver.ns1', true),
        'type' => 'text'
    ],
    'ns2' => [
        'label' => Language::_('OpenProvider.nameserver.ns2', true),
        'type' => 'text'
    ],
    'ns3' => [
        'label' => Language::_('OpenProvider.nameserver.ns3', true),
        'type' => 'text'
    ],
    'ns4' => [
        'label' => Language::_('OpenProvider.nameserver.ns4', true),
        'type' => 'text'
    ],
    'ns5' => [
        'label' => Language::_('OpenProvider.nameserver.ns5', true),
        'type' => 'text'
    ]
]);

// Whois fields
Configure::set('Namesilo.whois_fields', [
    /*
    'nickname' => array(
        'label' => Language::_("Namesilo.whois.Nickname", true),
        'type' => "text",
        'key' => 'nn',
    ),
    */
    'first_name' => [
        'label' => Language::_('Namesilo.whois.FirstName', true),
        'type' => 'text',
        'rp' => 'fn',
        'lp' => 'first_name',
    ],
    'last_name' => [
        'label' => Language::_('Namesilo.whois.LastName', true),
        'type' => 'text',
        'rp' => 'ln',
        'lp' => 'last_name',
    ],
    'company' => [
        'label' => Language::_('Namesilo.whois.Organization', true),
        'type' => 'text',
        'rp' => 'cp',
        'lp' => 'company',
    ],
    'address' => [
        'label' => Language::_('Namesilo.whois.Address1', true),
        'type' => 'text',
        'rp' => 'ad',
        'lp' => 'address1',
    ],
    'address2' => [
        'label' => Language::_('Namesilo.whois.Address2', true),
        'type' => 'text',
        'rp' => 'ad2',
        'lp' => 'address2',
    ],
    'city' => [
        'label' => Language::_('Namesilo.whois.City', true),
        'type' => 'text',
        'rp' => 'cy',
        'lp' => 'city',
    ],
    'state' => [
        'label' => Language::_('Namesilo.whois.StateProvince', true),
        'type' => 'text',
        'rp' => 'st',
        'lp' => 'state',
    ],
    'zip' => [
        'label' => Language::_('Namesilo.whois.PostalCode', true),
        'type' => 'text',
        'rp' => 'zp',
        'lp' => 'zip',
    ],
    'country' => [
        'label' => Language::_('Namesilo.whois.Country', true),
        'type' => 'text',
        'rp' => 'ct',
        'lp' => 'country',
    ],
    'phone' => [
        'label' => Language::_('Namesilo.whois.Phone', true),
        'type' => 'text',
        'rp' => 'ph',
        'lp' => 'phone',
    ],
    'email' => [
        'label' => Language::_('Namesilo.whois.EmailAddress', true),
        'type' => 'text',
        'rp' => 'em',
        'lp' => 'email',
    ],
]);
