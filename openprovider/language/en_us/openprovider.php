<?php

// Module config - main
$lang['OpenProvider.config.name']        = 'Openprovider';
$lang['OpenProvider.config.description'] = 'Openprovider module for Blesta.';

// Module config - module section
$lang['OpenProvider.config.module.row']      = 'Registrar Account';
$lang['OpenProvider.config.module.rows']     = 'Registrar Accounts';
$lang['OpenProvider.config.module.group']    = 'Registrar Accounts';
$lang['OpenProvider.config.module.row_keys'] = '';

// Module manage page
$lang['OpenProvider.manage.add_row_btn']                  = 'Add Account';
$lang['OpenProvider.manage.module_rows_title']            = 'Account list';
$lang['OpenProvider.manage.module_rows_no_results']       = 'There are no accounts.';
$lang['OpenProvider.manage.module_rows_header.username']  = 'Username';
$lang['OpenProvider.manage.module_rows_header.test_mode'] = 'Test mode';
$lang['OpenProvider.manage.module_rows_header.options']   = 'Options';
$lang['OpenProvider.manage.module_rows.edit']             = 'Edit';
$lang['OpenProvider.manage.module_rows.delete']           = 'Delete';
$lang['OpenProvider.manage.module_rows.confirm_delete']   = 'Are you sure you want to delete this account?';
$lang['OpenProvider.manage.manage_packages']              = 'Manage Packages';

// Module manage add row page
$lang['OpenProvider.add_row.box_title']   = 'Add OpenProvider Account';
$lang['OpenProvider.add_row.basic_title'] = 'Basic Settings';
$lang['OpenProvider.add_row.add_btn']     = 'Add Account';

// Module manage edit row page
$lang['OpenProvider.edit_row.box_title']   = 'Edit OpenProvider Account';
$lang['OpenProvider.edit_row.basic_title'] = 'Basic Settings';
$lang['OpenProvider.edit_row.add_btn']     = 'Update Account';

// Row meta
$lang['OpenProvider.row_meta.username']        = 'Username';
$lang['OpenProvider.row_meta.password']        = 'Password';
$lang['OpenProvider.row_meta.test_mode']       = 'Test mode';
$lang['OpenProvider.row_meta.test_mode_true']  = 'Yes';
$lang['OpenProvider.row_meta.test_mode_false'] = 'No';

// Package fields
$lang['OpenProvider.package_fields.type']        = 'Type';
$lang['OpenProvider.package_fields.type_domain'] = 'Domain Registration';
$lang['OpenProvider.package_fields.type_ssl']    = 'SSL Certificate';
$lang['OpenProvider.package_fields.tld_options'] = 'TLDs';

// Name servers
$lang['OpenProvider.nameserver.ns1'] = 'Name Server 1';
$lang['OpenProvider.nameserver.ns2'] = 'Name Server 2';
$lang['OpenProvider.nameserver.ns3'] = 'Name Server 3';
$lang['OpenProvider.nameserver.ns4'] = 'Name Server 4';
$lang['OpenProvider.nameserver.ns5'] = 'Name Server 5';

// Domain Fields
$lang['OpenProvider.domain.domain']                                          = 'Domain Name';
$lang['OpenProvider.domain.identification_type']                             = 'Identification Type';
$lang['OpenProvider.domain.identification_type.passport_number']             = 'Passport Number';
$lang['OpenProvider.domain.identification_type.passport_series']             = 'Passport Series';
$lang['OpenProvider.domain.identification_type.company_registration_number'] = 'Company Registration Number';

// Domain transfer fields
$lang['OpenProvider.transfer.domain']  = 'Domain Name';
$lang['OpenProvider.transfer.EPPCode'] = 'EPP Code';

$lang['OpenProvider.tab_nameservers.title']        = 'Nameservers';
$lang['OpenProvider.tab_nameserver.field_ns']      = 'Name Server %1$s'; // %1$s is the name server number
$lang['OpenProvider.tab_nameservers.field_submit'] = 'Update Name Servers';

$lang['OpenProvider.tab_domain_contacts.title']                          = 'Domain Contacts';
$lang['OpenProvider.tab_domain_contacts.field_submit']                   = 'Update Domain Contacts';
$lang['OpenProvider.tab_domain_contacts.contact_type.admin']             = 'Admin';
$lang['OpenProvider.tab_domain_contacts.contact_type.billing']           = 'Billing';
$lang['OpenProvider.tab_domain_contacts.contact_type.tech']              = 'Tech';
$lang['OpenProvider.tab_domain_contacts.contact_type.owner']             = 'Owner';
$lang['OpenProvider.tab_domain_contacts.field.first_name']               = 'First name';
$lang['OpenProvider.tab_domain_contacts.field.last_name']                = 'Last name';
$lang['OpenProvider.tab_domain_contacts.field.middle_name']              = 'Middle name';
$lang['OpenProvider.tab_domain_contacts.field.email']                    = 'Email';
$lang['OpenProvider.tab_domain_contacts.field.phone_number']             = 'Phone';
$lang['OpenProvider.tab_domain_contacts.field.company_name']             = 'Company';
$lang['OpenProvider.tab_domain_contacts.field.address']                  = 'Address';
$lang['OpenProvider.tab_domain_contacts.field.city']                     = 'City';
$lang['OpenProvider.tab_domain_contacts.field.state']                    = 'State';
$lang['OpenProvider.tab_domain_contacts.field.country']                  = 'Country';
$lang['OpenProvider.tab_domain_contacts.field.zipcode']                  = 'Zipcode';
$lang['OpenProvider.tab_domain_contacts.field.vat']                      = 'Vat';
$lang['OpenProvider.tab_domain_contacts.field.company_or_individual_id'] = 'Company or Individual ID';

$lang['OpenProvider.tab_settings.title']                 = 'Settings';
$lang['OpenProvider.tab_settings.field.epp']             = 'EPP Code/Transfer Key';
$lang['OpenProvider.tab_settings.field.submit']          = 'Update Settings';
$lang['OpenProvider.tab_settings.message.epp_code']      = 'Epp code: %s'; // %s is epp code
$lang['OpenProvider.tab_settings.field.is_locked_true']  = 'Domain transfer lock is enabled. Switch to disable it.';
$lang['OpenProvider.tab_settings.field.is_locked_false'] = 'Domain transfer lock is disabled. Switch to enable it.';

// Errors add/update row
$lang['OpenProvider.!error.username.empty']            = 'Username field shouldn\'t be empty!';
$lang['OpenProvider.!error.password.empty']            = 'Password field shouldn\'t be empty!';
$lang['OpenProvider.!error.password.valid_connection'] = 'Oops, something wrong: The username, password and test mode combination appear to be invalid, or your OpenProvider account may not be configured to allow API access.';
$lang['OpenProvider.!error.client.not_exist']          = 'Client not exist!';
$lang['OpenProvider.!error.client.phone_not_exist']    = 'Client`s phone number not exists!';
$lang['OpenProvider.!error.epp.empty']                 = 'Domain transfers require an EPP code to be entered.';
$lang['OpenProvider.!error.passport_number.empty']     = 'This tld require an passport number to be entered.';
$lang['OpenProvider.!error.passport_series.empty']     = 'This tld require an passport series to be entered.';
$lang['OpenProvider.!error.domain.not_exist']          = 'This domain not exist in Openprovider.';
$lang['OpenProvider.!error.domain.name_undefined']     = 'Domain name undefined.';
