<?php 
include 'libs/core.php'; 

if (isset($_GET['r']) && !isset($_COOKIE['ref'])) {
	$reff = $mysqli->real_escape_string($_GET['r']);
	setcookie('ref',  $reff, time()+86400000);
}

if (isset($_POST['address']) and isset($_POST['token'])) { 
	
    # clean user's input
	$address = $mysqli->real_escape_string($_POST['address']);
	if (!isset($_COOKIE['address'])) {
		setcookie('address', $address, time()+1000000);
	} 
    # end 
	if ($_POST['token'] == $_SESSION['token']) {

		# check captcha
		if (isset($_POST['g-recaptcha-response']) && $faucet['captcha'] == 'recaptcha') {
			$secret = get_info(15);
			$CaptchaCheck = json_decode(captcha_check($_POST['g-recaptcha-response'], $secret))->success; 
		} else {
			$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Invalid Captcha</div></center>"; 
		}
		if ($CaptchaCheck and !isset($alert)) {
			if (check_blocked_ip($ip) == 'blocked') {
				$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Your Ip Is Blocked. Please Contact Admin.</div></center>";
			} elseif (check_blocked_address($address) == 'blocked') {
				$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Your Address Is Blocked. Please Contact Admin.</div></center>";
			} elseif (!empty(get_info(29)) and iphub(get_info(29)) == 'bad') {
				$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Your Ip Is Blocked By IpHub</div></center>";
				$mysqli->query("INSERT INTO ip_blocked (address) VALUES ('$ip')");
			} elseif (checkaddress($address) !== 'ok') {
				$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Your Address is not ready to claim!</div><br><div id='CountDownTimer' data-timer='" . checkaddress($address) . "' style='width: 100%;'></div></center>";
			} elseif (checkip($ip) !== 'ok') {
				$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Your Ip Address is not ready to claim!</div><br><div id='CountDownTimer' data-timer='" . checkip($ip) . "' style='width: 100%;'></div></center>";
			} else {
				
				# check short link
				if (get_info(12) == 'on' or (isset($_POST['link']) and get_info(10) == 'on')) {
					$key = get_token(15); 
					for ($i=1; $i <= count($link); $i++) { 
						if (!isset($_COOKIE[$i])) {
							$mysqli->query("INSERT INTO link (address, sec_key, ip) VALUES ('$address', '$key', '$ip')");
							log_user($address, $ip);
							setcookie($i, 'Link Already Visited', time() + 86400);
							$url = $link[$i];
							$full_url = str_replace("{key}",$key,$url);
							$short_link = file_get_contents($full_url);
							break;
						}
					}
					header("Location: ". $short_link);
					exit();
				} else {

					#normal claim
					$faucetpay_api = get_info(6);
					$currency = $faucet['currency'];
					$faucetpay = new FaucetPay($faucetpay_api, $currency);
					$result = $faucetpay->send($address, $faucet['reward'], $ip);
					if (isset($_COOKIE['ref']) && $address !== $_COOKIE['ref']) {
						$ref = $mysqli->real_escape_string($_COOKIE['ref']);
						$amt = floor($faucet['reward'] / 100 * $faucet['ref']);
						$s = $faucetpay->sendReferralEarnings($ref, $amt);
					}
					if ($result['success'] == true) {
						log_user($address, $ip);
						$new_balance = $result['balance'];
						$mysqli->query("UPDATE settings SET value = '$new_balance' WHERE id = '30'");
						$alert = "<center><img style='max-width: 200px;' src='template/img/trophy.png'><br>{$result['html']}</center>";
					} else {
						$alert = "<center><img style='max-width: 200px;' src='template/img/trophy.png'><br><div class='alert alert-danger'>Failed to send your reward :(</div></center>"; 
					}
				}
			}
		} else {
			$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Invalid Captcha</div></center>"; 
		}
	} else {
		$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Invalid Token</div></center>"; 
	}
}

// check if user has completed a short link
if (isset($_GET['k'])) {
	$key = $mysqli->real_escape_string($_GET['k']);
	$check = $mysqli->query("SELECT * FROM link WHERE sec_key = '$key' and ip = '$ip' LIMIT 1");
	if ($check->num_rows == 1) { 
		$check = $check->fetch_assoc();
		$address = $check['address'];
		$mysqli->query("DELETE FROM link WHERE sec_key = '$key'");
		$faucetpay_api = get_info(6);
		$faucetpay = new FaucetPay($faucetpay_api, $faucet['currency']);
		$rew = get_info(11) + $faucet['reward'];
		$result = $faucetpay->send($address, $rew, $ip);
		$new_balance = $result['balance'];
		$mysqli->query("UPDATE settings SET value = '$new_balance' WHERE id = '30'");
		if (isset($_COOKIE['ref']) && $address !== $_COOKIE['ref']) {
			$ref = $mysqli->real_escape_string($_COOKIE['ref']);
			$amt = floor($rew / 100 * $faucet['ref']);
			$s = $faucetpay->sendReferralEarnings($ref, $amt);
		}
		$alert = "<center><img style='max-width: 200px;' src='template/img/trophy.png'><br>{$result['html']}</center>";
	} else {
		$alert = "<center><img style='max-width: 200px;' src='template/img/bots.png'><br><div class='alert alert-warning'>Invalid Key !</div></center>";
	}
}

$_SESSION['token'] = get_token(70);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?=$faucet['name']?> - <?=$faucet['description']?></title> 
	<link rel="shortcut icon" href="template//img/favicon.ico" type="image/x-icon">
	<link rel="icon" href="template/img/favicon.ico" type="template/image/x-icon">
	<!-- ===== Google Fonts | Roboto ===== -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

	<!-- ===== Custom Stylesheets ===== -->
	<link rel="stylesheet" type="text/css" href="template/css/<?=$faucet['theme']?>.css"> 
	<link rel="stylesheet" href="template/css/countdown.css"> 
	<style type="text/css"> 
	body {  
		font-family: 'Roboto', sans-serif;
	}
	img, iframe {
		max-width: 100%;
	}
	.mt-10 {
		margin-top: 10px;
	}
	.mt-20 {
		margin-top: 20px;
	}
	.mt-30 {
		margin-top: 30px;
	}
	.login {
		background-color: rgba(226, 212, 296, 0.3);
		padding-top: 20px;
		padding-bottom: 20px;
		border-radius: 20px 20px 20px 20px;
	}
	.login:hover {
		background-color: rgba(226, 212, 296, 0.5);
	}
	.alert {
		margin-bottom: 20px;
	}  
	footer, .cbc {
		color: #F67F7F;
	}	
	
</style>
</head>
<body> 
	<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
		<a class="navbar-brand" href="<?=$fullDomain?>"><?=$faucet['name']?></a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarColor01">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item active">
					<a class="nav-link" href="<?=$fullDomain?>"><i class="fa fa-home" aria-hidden="true"></i> Home <span class="sr-only">(current)</span></a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="#"><i class="fa fa-info" aria-hidden="true"></i> About us</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="#"><i class="fa fa-envelope-open" aria-hidden="true"></i> Contact</a>
				</li>
			</ul>
			<ul class="navbar-nav ml-auto">
				<li class="nav-item active">
					<a class="nav-link" href="#"><i class="fa fa-balance-scale" aria-hidden="true"></i> Balance: <?=(get_info(30) == 0) ? "Make a claim to update it" : ((number_format(get_info(30) / 100000000, 8, '.', '') . $currency_name))?></a>
				</li>
			</ul>
		</div>
	</nav>

	<center class="mt-10">
		<?= ($ad['top'])?: '<img src="https://placehold.co/728x90">';?>
	</center>

	<div class="container-fluid mt-30">
		<div class="row">
			<div class="col-sm-3 text-center mt-20">
			<?= ($ad['left'])?: '<img src="https://placehold.co/160x600">';?>	
			</div>
			<div class="col-sm-6 login">
				<div class="alert alert-success text-center" style="margin-top: 10px;">
					<p class="mb-0"><i class="fa fa-trophy" aria-hidden="true"></i> Claim <strong><?=number_format($faucet['reward'] / 100000000, 8, '.', '')?> <?=$currency_name?></strong> every <strong><?=floor($faucet['timer']/60)?></strong> minutes .</p>
				</div>
				<center>
					<?= ($ad['above-form'])?: '<img src="https://placehold.co/728x90">';?>
				</center>
				<?php if (isset($alert)) { ?>
				<div class="modal fade" id="alert" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
					<div class="modal-dialog" role="document">
						<div class="modal-content"> 
							<div class="modal-body">
								<?=$alert?>  
							</div>
						</div>
					</div>
				</div>
				<?php } if (checkip($ip) == 'ok') { ?>
				<form action="" method="post">
					<input type="hidden" name="token" value="<?=$_SESSION['token']?>">
					<div class="form-group">
						<span class="badge badge-warning control-label">Your <?=$currency_name?> Address</span>
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><img src="template/img/wallet.png" width="40px"></div>
								<input type="text" class="form-control" name="address" <?php if(isset($_COOKIE['address'])) {echo "value='" . $_COOKIE['address'] . "'";} else {echo 'placeholder="Your '.  $currency_name . ' Address"'; } ?> style="border-radius: 0px 20px 20px 0px;">
							</div>
						</div>
					</div> 
					<center>
						<?= ($ad['bottom'])?: '<img src="https://placehold.co/728x90">';?>
					</center>
					<div class="form-group">
						<span class="badge badge-danger control-label">Complete Captcha</span>
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><img src="template/img/captcha.png" width="40px"></div>
								<?=$captcha_display?>
							</div>
						</div>
					</div>
					<?php if (get_info(10) == 'on' and get_info(12) !== 'on') { 
						for ($i=1; $i <= count($link); $i++) { 
							if (!isset($_COOKIE[$i])) { ?>
					<label class="custom-control custom-checkbox mb-2 mr-sm-2 mb-sm-0">
						<input type="checkbox" name="link" value="yes" class="custom-control-input" checked>
						<span class="custom-control-indicator"></span>
						<span class="custom-control-description">
							<i class="fa fa-gift" aria-hidden="true"></i> <strong>I want <b style="color: #F67F7F"><?= number_format(get_info(11) / 100000000, 8, '.', '')?> <?=$currency_name?> bonus</b> by completing <b style="color: #F67F7F">SHORT LINK</b></strong>
						</span>
					</label> 
					<?php break; } }} ?>
					<button type="button" class="btn btn-warning btn-lg btn-block" style="margin-bottom: 20px;" data-toggle="modal" data-target="#next"><i class="fa fa-paper-plane" aria-hidden="true"></i> <strong>Claim Your <?=$currency_name?></strong></button>
					<div class="modal fade bd-example-modal-lg" id="next" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
						<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title" id="exampleModalLabel">Final Step</h5>
									<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										<span aria-hidden="true">&times;</span>
									</button>
								</div>
								<div class="modal-body">
									<?= ($ad['modal'])?: '<img src="https://placehold.co/300x250">';?>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
									<button type="submit" class="btn btn-primary" id="claim">Claim</button>
								</div>
							</div>
						</div>
					</div>
					<code>Ref link: <?=$faucet['url']?>?r=Your_<?=$currency_name?>_address</code>
				</form>
				<?php } 
				else { 
					$wait= 1; 
					echo "<div class='alert alert-info mt-20'>You have to wait</div><br><div id='CountDownTimer' data-timer='" . checkip($ip) . "' style='width: 100%;'></div>"; 
				} ?> 
			</div>
			<div class="col-sm-3 text-center mt-20">
				<?= ($ad['right'])?: '<img src="https://placehold.co/160x600">';?>
			</div>
		</div>
	</div>
	<br>
	<footer class="text-center mt-3">
		<p>&copy; <?= date('Y') ?> <a style="color: #fff;font-weight: bold;" href='<?=$faucet['url']?>'><?=$faucet['name']?></a>, <span id='copyright'>Powered by <a style="font-weight: bold;" href='https://github.com/BlazeCoderLab/CoinFlow-Faucet-Script' target="_blank" class="cbc">CoinFlow Script</a></span></p>
	</footer> 
	<script src="template/js/jquery-3.2.1.min.js"></script>
	<script src="template/js/popper.min.js"></script>
	<script src="template/js/bootstrap.min.js"></script>
	<script src="https://use.fontawesome.com/7002d3875b.js"></script>
	<script src="template/js/adblock.js"></script>
	<?php if (isset($alert)) { ?>
	<script type='text/javascript'>$('#alert').modal('show');</script>
	<?php  } ?>
	<script type="text/javascript"> var fauceturl = '<?=$faucet['url']?>'; </script>
	<script type="text/javascript" src="template/js/timer.js"></script>
	<script type="text/javascript" src="template/js/faucet.js"></script>
</body>
</html>
<?php
$mysqli->close();
?>
