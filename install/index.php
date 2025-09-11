<?php
include '../libs/config.php';

$mysqli->query("CREATE TABLE IF NOT EXISTS `address_blocked` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); 

$mysqli->query("CREATE TABLE IF NOT EXISTS `address_list` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(75) NOT NULL,
  `total_claimed` varchar(100) NOT NULL,
  `ref` varchar(75) NOT NULL,
  `last` int(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); 

$mysqli->query("CREATE TABLE IF NOT EXISTS `ip_blocked` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); 

$mysqli->query("CREATE TABLE IF NOT EXISTS `ip_list` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(50) NOT NULL,
  `last` int(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); 

$mysqli->query("CREATE TABLE IF NOT EXISTS `link` (
  `address` varchar(75) NOT NULL,
  `sec_key` varchar(75) NOT NULL,
  `ip` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$mysqli->query("CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$fullDomainEscaped = $mysqli->real_escape_string($fullDomain);

$mysqli->query("INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'Name', 'CoinFlow'),
(2, 'Description', 'Sleek, Fast and Lightweight Crypto Faucet Script'),
(3, 'Url', '$fullDomainEscaped'),
(4, 'Theme', 'main'),
(5, 'Currency', 'TRX'),
(6, 'FaucetPay Api', ''),
(7, 'Timer', '60'),
(8, 'Reward', '100'),
(9, 'Referral Commision', '10'),
(10, 'Short Link Status', 'off'),
(11, 'Short Link Reward', '0'),
(12, 'Force Short Link', 'off'),
(13, 'Captcha System', 'recaptcha'),
(14, 'Recaptcha Public Key', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'),
(15, 'Recaptcha Secret Key', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'),
(23, 'Top Ad Slot', ''),
(24, 'Left Ad Slot', ''),
(25, 'Right Ad Slot', ''),
(26, 'Above Form Ad Slot', ''),
(27, 'Bottom Ad Slot', ''),
(28, 'Modal Ad Slot', ''),
(29, 'IpHub Api', ''),
(30, 'Faucet Balance', '0');");

echo "<h2 style='text-align:center;'>Installation completed, now please delete the <b style='color: blue;'>install folder</b> for security reasons</h2>";

?>