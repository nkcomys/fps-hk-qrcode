<?php

function fps_pad($string, $size) {
    if($size<2) $size = 2;
    return str_pad($string, $size, "0", STR_PAD_LEFT);
}

function crc16($data)
{
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++)
    {
        $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
        $x ^= $x >> 4;
        $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
    }
    return $crc;
}

function dataObj($id, $value) {
    $paddedLength = fps_pad(strlen($value)."",2);
    return $id.$paddedLength.$value;
}

function emvEncode($obj) {
    $payloadFormatIndicator = dataObj("00", "01");
    $pointOfInitiationMethod = dataObj("01", ($obj['amount'] == "") ? "11" : "12");

    $guid = dataObj("00", "hk.com.hkicl");
    $merchantAccountInformationTemplate = "";

    switch ($obj['account']) {
        case "02":
            $merchantAccountInformationTemplate = dataObj("02", $obj['fps_id']);
            break;
        case "03":
            $merchantAccountInformationTemplate = dataObj("01", $obj['bank_code']).dataObj("03", $obj['mobile']);
            break;
        case "04":
            $merchantAccountInformationTemplate = dataObj("01", $obj['bank_code']).dataObj("04", strtoupper($obj['email']));
            break;
    }

    $merchantAccountInformation = dataObj("26", $guid.$merchantAccountInformationTemplate);
    $merchantCategoryCode = dataObj("52", $obj['mcc']);
    $transactionCurrency = dataObj("53", $obj['currency']);
    $countryCode = dataObj("58", "HK");
    $merchantName = dataObj("59", "NA");
    $merchantCity = dataObj("60", "HK");
    $transactionAmount = ($obj['amount'] == "") ? "" : dataObj("54", $obj['amount']);
    $reference = ($obj['reference'] == "") ? "" : dataObj("05", $obj['reference']);
    $additionalDataTemplate = ($reference == "") ? "" : dataObj("62", $reference);

    $msg = "";
    $msg .= $payloadFormatIndicator;
    $msg .= $pointOfInitiationMethod;
    $msg .= $merchantAccountInformation;
    $msg .= $merchantCategoryCode;
    $msg .= $transactionCurrency;
    $msg .= $countryCode;
    $msg .= $merchantName;
    $msg .= $merchantCity;
    $msg .= $transactionAmount;
    $msg .= $additionalDataTemplate;
    $msg .= "6304";

    return $msg;
}

$msg = emvEncode([
    "account"=>'02',
    "bank_code"=>'',
    "fps_id"=>'104807383',
    "mobile"=>'',
    "email"=>'',
    "mcc"=>'0000',
    "currency"=>'344',
    "amount"=>'9999',
    "reference"=>'123123'
]);

$hash = strtoupper(fps_pad(dechex(crc16($msg)),4));
$message = $msg.$hash;

echo $message;