
CREATE TABLE IF NOT EXISTS `data` (
  `row_id` int(10) UNSIGNED NOT NULL,
  `host_id` smallint(5) UNSIGNED NOT NULL,
  `streak_id` mediumint(8) UNSIGNED NOT NULL,
  `ping_status` tinyint(4) NOT NULL,
  `ping_state` tinyint(3) UNSIGNED NOT NULL,
  `ping_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `host_id_idx` (`host_id`),
  KEY `ping_datetime_idx` (`ping_datetime`) USING BTREE
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `hosts` (
  `host_id` smallint(5) UNSIGNED NOT NULL,
  `host_label` varchar(64) NOT NULL,
  `host_fqdn` varchar(255) NOT NULL,
  `host_enabled` tinyint(3) UNSIGNED NOT NULL,
  PRIMARY KEY (`host_id`),
  UNIQUE KEY `host_label_unq` (`host_label`),
  UNIQUE KEY `host_fqdn_unq` (`host_fqdn`) USING BTREE
) DEFAULT CHARSET=utf8;
