<?php

// Domain fields
Configure::set('OpenProvider.domain_fields', [
    'domain' => [
        'label' => Language::_('OpenProvider.domain.domain', true),
        'type' => 'text'
    ],
]);

// Transfer fields
Configure::set('OpenProvider.transfer_fields', [
    'domain' => [
        'label' => Language::_('OpenProvider.transfer.domain', true),
        'type' => 'text'
    ],
    'auth' => [
        'label' => Language::_('OpenProvider.transfer.EPPCode', true),
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

// .ES
Configure::set('OpenProvider.domain_fields.es', [
    'identification_type' => [
        'label' => Language::_('OpenProvider.domain.identification_type', true),
        'type' => 'select',
        'options' => [
            'passport_number' => Language::_('OpenProvider.domain.identification_type.passport_number', true),
            'company_registration_number' => Language::_('OpenProvider.domain.identification_type.company_registration_number', true),
        ]
    ],
    'passport_number' => [
        'label' => Language::_('OpenProvider.domain.identification_type.passport_number', true),
        'type'  => 'text',
    ],
    'passport_series' => [
        'label' => Language::_('OpenProvider.domain.identification_type.passport_series', true),
        'type'  => 'text',
    ],
    'company_registration_number' => [
        'label' => Language::_('OpenProvider.domain.identification_type.company_registration_number', true),
        'type' => 'text',
    ],
]);

Configure::set('OpenProvider.domain_fields.com.es', Configure::get('OpenProvider.domain_fields.es'));
Configure::set('OpenProvider.domain_fields.nom.es', Configure::get('OpenProvider.domain_fields.es'));
Configure::set('OpenProvider.domain_fields.edu.es', Configure::get('OpenProvider.domain_fields.es'));
Configure::set('OpenProvider.domain_fields.org.es', Configure::get('OpenProvider.domain_fields.es'));
