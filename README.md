# Openprovider Domains module for Blesta - Beta version

Offer your customers almost any TLD, from the most popular to the most exotic, all from one registrar! 

# Getting started

- Copy the folder `openprovider/` into the `<blesta root>/components/modules/` folder of your Blesta instance
- Create a (free!) [Openprovider account](https://openprovider.com/) if you haven't already.
- Navigate to `<blesta root>/components/modules/openprovider/config/openprovider.php`and configure the first array with the TLDs which you would like to support. Check your Openprovider account for a full list of the more than 1800 TLDs available for registration. Please note that in the Beta version of the module, not all TLDs can be automatically registered.

```php
// Allowed tlds
Configure::set('OpenProvider.tlds', [
    '.com',
    '.nl',
    '.shop', 
    //add desired TLDs here
]);
```

- Follow the instructions from the Blesta tutorial for [how to sell domains](https://docs.blesta.com/display/user/Selling+Domains#SellingDomains-Installadomainmodule)

- We want the module to reflect your needs and feedback is welcome. Please contact us: **integrations@openprovider.com**


## SQL code to create necessary tables for module

It may be helpfully if you have error with tables because they are not exists or broken.

### openprovider_token
```
CREATE TABLE `openprovider_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `until_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_hash` (`user_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
```

### openprovider_handles
```
CREATE TABLE `openprovider_handles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `service_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
```

### openprovider_mapping_service_domain
```
CREATE TABLE `openprovider_mapping_service_domain` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(10) unsigned NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
```
