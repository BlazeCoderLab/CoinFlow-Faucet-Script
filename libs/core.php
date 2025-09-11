<?php
session_start();
include 'config.php';
include 'function.php';
include 'faucetpay.php';

$ip = get_ip();
$time = time();

$faucet['name'] = get_info(1);
$faucet['description'] = get_info(2);
$faucet['url'] = get_info(3);
$faucet['theme'] = get_info(4);
$faucet['currency'] = get_info(5);
$faucet['timer'] = get_info(7);
$faucet['reward'] = get_info(8);
$faucet['comission'] = get_info(10);
$faucet['captcha'] = get_info(13);
$ad['top'] = get_info(23);
$ad['left'] = get_info(24);
$ad['right'] = get_info(25);
$ad['above-form'] = get_info(26);
$ad['bottom'] = get_info(27);
$ad['modal'] = get_info(28);

$currency_name = $faucet['currency'];

# get captcha
switch ($faucet['captcha']) {
  case 'recaptcha':
  $publickey = get_info(14);
  $captcha_display = "<div class='g-recaptcha' data-sitekey='{$publickey}' style='margin-left: 3px;'></div><script src='https://www.google.com/recaptcha/api.js' async defer></script>";
  break;
}

?>
