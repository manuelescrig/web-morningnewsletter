<?php

// Helpers
function color(float $value) {
  return ($value > 0 ? 'color:#2ECC40;' : ' color:#FF4136;'); 
}

$appName = 'Morning Digests';
$postReplyTo  = 'manuelescrig@gmail.com';
$postAlias  = 'Morning Digests';
$postEmail  = 'manuelescrig@gmail.com';
$postSubject  =  $appName;

// Markets
$urlMarkets = 'https://financialmodelingprep.com/api/v3/quote/%5EDJI,%5EGSPC,%5EFTSE';
$objMarkets = json_decode(file_get_contents($urlMarkets), true);

$marketsBlock = "";
$marketsBlock .= "<table style=\"background-color: #fefefe;\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"92%\">";
$marketsBlock .= "<tr>";
$marketsBlock .= "<td align=\"left\" valign=\"top\" width=\"16%\" class=\"templateColumnContainer\"><strong>Market</strong></td>";
$marketsBlock .= "<td align=\"right\" width=\"10%\"><strong>Price</strong></th>";
$marketsBlock .= "<td align=\"right\" width=\"6%\"><strong>%</strong></td>";
$marketsBlock .= "<td align=\"right\" width=\"12%\"><strong>Change</strong></td>";
$marketsBlock .= "<td align=\"right\" width=\"12%\"><strong>Day Low</strong></td>";
$marketsBlock .= "<td align=\"right\" width=\"12%\"><strong>Day High</strong></td>";
$marketsBlock .= "<td align=\"right\" width=\"12%\"><strong>Year Low</strong></td>";
$marketsBlock .= "<td align=\"right\" width=\"12%\"><strong>Year High</strong></td>";
$marketsBlock .= "</tr>";
$marketsBlock .= "</table>";

foreach($objMarkets as $item) {
  $name = $item['symbol']; 
  $price = number_format($item['price'], 2, ',', '.'); 
  $change = number_format($item['change'], 2, ',', '.'); 
  $changePercentage = number_format($item['changesPercentage'], 2, ',', '.'); 
  if ($item['changesPercentage'] > 0) {
    $changePercentage = sprintf("%+.2f", $item['changesPercentage']);
  } 
  $dayLow = number_format($item['dayLow'], 2, ',', '.');
  $dayHigh = number_format($item['dayHigh'], 2, ',', '.');
  $yearLow = number_format($item['yearLow'], 2, ',', '.');
  $yearHigh = number_format($item['yearHigh'], 2, ',', '.');
  $color = color($item['change']);
  $marketsBlock .= "<table style=\"background-color: #fefefe;\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"92%\">";
  $marketsBlock .= "<tr>";
  $marketsBlock .= "<td align=\"left\" valign=\"top\" width=\"16%\" class=\"templateColumnContainer\"><strong>$name</strong></td>";
  $marketsBlock .= "<td align=\"right\" width=\"10%\">$price</th>";
  $marketsBlock .= "<td align=\"right\" width=\"6%\"><span style=\"$color\"><strong>$changePercentage%</strong></style></td>";
  $marketsBlock .= "<td align=\"right\" width=\"12%\"><span style=\"$color\">$change</style></td>";
  $marketsBlock .= "<td align=\"right\" width=\"12%\">$dayLow</td>";
  $marketsBlock .= "<td align=\"right\" width=\"12%\">$dayHigh</td>";
  $marketsBlock .= "<td align=\"right\" width=\"12%\">$yearLow</td>";
  $marketsBlock .= "<td align=\"right\" width=\"12%\">$yearHigh</td>";
  $marketsBlock .= "</tr>";
  $marketsBlock .= "</table>";
}

// Stocks
$urlStocks = 'https://financialmodelingprep.com/api/v3/quote/AAPL,BOX,FB,GE,GOOG,MSFT,TWTR,UAA';
$objStocks = json_decode(file_get_contents($urlStocks), true);

$stocksBlock = "";
$stocksBlock .= "<table style=\"background-color: #fefefe;\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"92%\">";
$stocksBlock .= "<tr>";
$stocksBlock .= "<td align=\"left\" valign=\"top\" width=\"16%\" class=\"templateColumnContainer\"><strong>Stock</strong></td>";
$stocksBlock .= "<td align=\"right\" width=\"10%\"><strong>Price</strong></th>";
$stocksBlock .= "<td align=\"right\" width=\"6%\"><strong>%</strong></td>";
$stocksBlock .= "<td align=\"right\" width=\"12%\"><strong>Change</strong></td>";
$stocksBlock .= "<td align=\"right\" width=\"12%\"><strong>Day Low</strong></td>";
$stocksBlock .= "<td align=\"right\" width=\"12%\"><strong>Day High</strong></td>";
$stocksBlock .= "<td align=\"right\" width=\"12%\"><strong>Year Low</strong></td>";
$stocksBlock .= "<td align=\"right\" width=\"12%\"><strong>Year High</strong></td>";
$stocksBlock .= "</tr>";
$stocksBlock .= "</table>";

foreach($objStocks as $item) {
  $name = $item['symbol']; 
  $price = number_format($item['price'], 2, ',', '.'); 
  $change = number_format($item['change'], 2, ',', '.'); 
  $changePercentage = number_format($item['changesPercentage'], 2, ',', '.'); 
  if ($item['changesPercentage'] > 0) {
    $changePercentage = sprintf("%+.2f", $item['changesPercentage']);
  } 
  $dayLow = number_format($item['dayLow'], 2, ',', '.');
  $dayHigh = number_format($item['dayHigh'], 2, ',', '.');
  $yearLow = number_format($item['yearLow'], 2, ',', '.');
  $yearHigh = number_format($item['yearHigh'], 2, ',', '.');
  $color = color($item['change']);
  $stocksBlock .= "<table style=\"background-color: #fefefe;\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"92%\">";
  $stocksBlock .= "<tr>";
  $stocksBlock .= "<td align=\"left\" valign=\"top\" width=\"16%\" class=\"templateColumnContainer\"><strong>$name</strong></td>";
  $stocksBlock .= "<td align=\"right\" width=\"10%\">$price</th>";
  $stocksBlock .= "<td align=\"right\" width=\"6%\"><span style=\"$color\"><strong>$changePercentage%</strong></style></td>";
  $stocksBlock .= "<td align=\"right\" width=\"12%\"><span style=\"$color\">$change</style></td>";
  $stocksBlock .= "<td align=\"right\" width=\"12%\">$dayLow</td>";
  $stocksBlock .= "<td align=\"right\" width=\"12%\">$dayHigh</td>";
  $stocksBlock .= "<td align=\"right\" width=\"12%\">$yearLow</td>";
  $stocksBlock .= "<td align=\"right\" width=\"12%\">$yearHigh</td>";
  $stocksBlock .= "</tr>";
  $stocksBlock .= "</table>";
}

// Criptocurrencies
$urlCripto = 'https://financialmodelingprep.com/api/v3/quote/BTCUSD,ETHUSD,XRPUSD,XLMUSD,ADAUSD';
$objCripto = json_decode(file_get_contents($urlCripto), true);

$cryptoBlock = "";
$cryptoBlock .= "<table style=\"background-color: #fefefe;\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"92%\">";
$cryptoBlock .= "<tr>";
$cryptoBlock .= "<td align=\"left\" valign=\"top\" width=\"16%\" class=\"templateColumnContainer\"><strong>Criptocurrency</strong></td>";
$cryptoBlock .= "<td align=\"right\" width=\"10%\"><strong>Price</strong></th>";
$cryptoBlock .= "<td align=\"right\" width=\"6%\"><strong>%</strong></td>";
$cryptoBlock .= "<td align=\"right\" width=\"12%\"><strong>Change</strong></td>";
$cryptoBlock .= "<td align=\"right\" width=\"12%\"><strong>Day Low</strong></td>";
$cryptoBlock .= "<td align=\"right\" width=\"12%\"><strong>Day High</strong></td>";
$cryptoBlock .= "<td align=\"right\" width=\"12%\"><strong>Year Low</strong></td>";
$cryptoBlock .= "<td align=\"right\" width=\"12%\"><strong>Year High</strong></td>";
$cryptoBlock .= "</tr>";
$cryptoBlock .= "</table>";

foreach($objCripto as $item) {
  $name = $item['name']; 
  $price = number_format($item['price'], 2, ',', '.'); 
  $change = number_format($item['change'], 2, ',', '.'); 
  $changePercentage = number_format($item['changesPercentage'], 2, ',', '.'); 
  if ($item['changesPercentage'] > 0) {
    $changePercentage = sprintf("%+.2f", $item['changesPercentage']);
  } 
  $dayLow = number_format($item['dayLow'], 2, ',', '.');
  $dayHigh = number_format($item['dayHigh'], 2, ',', '.');
  $yearLow = number_format($item['yearLow'], 2, ',', '.');
  $yearHigh = number_format($item['yearHigh'], 2, ',', '.');
  $color = color($item['change']);
  $cryptoBlock .= "<table style=\"background-color: #fefefe;\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"92%\">";
  $cryptoBlock .= "<tr>";
  $cryptoBlock .= "<td align=\"left\" valign=\"top\" width=\"16%\" class=\"templateColumnContainer\"><strong>$name</strong></td>";
  $cryptoBlock .= "<td align=\"right\" width=\"10%\">$price</td>";
  $cryptoBlock .= "<td align=\"right\" width=\"6%\"><span style=\"$color\"><strong>$changePercentage%</strong></style></td>";
  $cryptoBlock .= "<td align=\"right\" width=\"12%\"><span style=\"$color\">$change</style></td>";
  $cryptoBlock .= "<td align=\"right\" width=\"12%\">$dayLow</td>";
  $cryptoBlock .= "<td align=\"right\" width=\"12%\">$dayHigh</td>";
  $cryptoBlock .= "<td align=\"right\" width=\"12%\">$yearLow</td>";
  $cryptoBlock .= "<td align=\"right\" width=\"12%\">$yearHigh</td>";
  $cryptoBlock .= "</tr>";
  $cryptoBlock .= "</table>";
}

$postMessage  =  
'
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Good Morning</title>
<style>
.flex-container {
  display: flex;
  justify-content: flex-start;
  min-width: 80px;
}
.flex-container > div {
  display: inline-flex;
  min-width: 100px;
  text-align: left;
  margin-bottom: 1px;
  padding: 2px;
}
h3   {
}
</style>
</head>
<body>

<br>
' . $marketsBlock .'
<br>
<br>
' . $stocksBlock .'
<br>
<br>
' . $cryptoBlock .'

<br>
<br>
https://morningdigests.com
<br>
https://morningnewsletter.com

</body>
</html>
';




$finalReplyTo = (empty($postReplyTo)) ? 'no-reply@emailmeapp.net' : $postReplyTo;
$finalAlias = (empty($postAlias)) ? $appName : $postAlias;
$finalSubject = (empty($postSubject)) ? '' : $postSubject;
$finalBody = (empty($postMessage)) ? '' : $postMessage;

// When there is no subject and no body, set a default subject
if (empty($finalSubject) && empty($finalBody)) {
  $finalSubject= $appName;
}

// Send Email
require(__DIR__.'/phpmailer/src/PHPMailer.php');
require(__DIR__.'/phpmailer/src/SMTP.php');
require(__DIR__.'/phpmailer/src/Exception.php');
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer;
$mail->CharSet    = 'UTF-8';
$mail->isHTML(true);
$mail->From       = 'info@emailmeapp.net';
$mail->Sender     = $finalReplyTo;
$mail->FromName   = $finalAlias;
$mail->Subject    = $finalSubject;
$mail->Body       = $finalBody;
$mail->AllowEmpty = true;
$mail->AddAddress($postEmail);
$mail->AddReplyTo($finalReplyTo, $finalAlias);


$result = $mail->send();

if ($debug) {
  echo "<p>";
  echo "<br/> RESULT <br/>";
  if ($result) {
    echo "Message has been sent successfully";
  } else {
    echo "Mailer Error = ", $mail->ErrorInfo;
  }
  echo "</p>";
  echo "</body>";
  echo "</html>";
} else {
  if ($result) {
    $response = array('mail' => true);
  } else {
    $response = array('mail' => false);
  }
  echo json_encode($response);
}

?>

