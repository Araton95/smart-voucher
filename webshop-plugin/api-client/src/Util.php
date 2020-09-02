<?php

namespace SmartVoucher;

use kornrunner\Keccak;
use kornrunner\Secp256k1;
use kornrunner\Serializer\HexSignatureSerializer;

use Elliptic\EC;
use Elliptic\EC\Signature;

class Util {
  
  /**
   * Utility function to sign message with a private key
   * return Signature string
   */
  public static function sign($message, $private_key) {
    $hashed_message = self::message_hash($message);
    
    $hash = str_replace('0x', '', $hashed_message);
    $private_key = str_replace('0x', '', $private_key);
    
    $ec = new EC('secp256k1');
    $signature = $ec->sign($hash, $private_key);
    return ( $signature->toDER('hex') . '1c');
  }
  
  private static function message_hash($data) {
    $messageHex = str_replace('0x', '', $data);
    $messageBytes = pack("H*", $messageHex);
    $messageBuffer = unpack('H*', $messageBytes);
    $preamble = "\x19Ethereum Signed Message:\n" . strlen($messageBytes);
    $preambleBuffer = unpack('H*', $preamble);
    $ethMessage =  array_merge($preambleBuffer , $messageBuffer);
    $str_1 = pack("H*", $ethMessage[0]);
    $str_2 = pack("H*", $ethMessage[1]);
    
    $str = $str_1 . $str_2;
    $keccak = Keccak::hash($str, 256);
    return '0x' . $keccak;
  }
}