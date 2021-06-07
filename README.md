# openprovider-domains-blesta

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
