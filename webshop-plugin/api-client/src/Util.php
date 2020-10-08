<?php

namespace SmartVoucher;

use kornrunner\Keccak;
use kornrunner\Secp256k1;
use BN\BN;

class Util {
  
  /**
   * sign message based on eth.account.sign
   * Utility function to sign message with a private key
   * return Signature string
   */
  public static function sign($message, $private_key) {
    $hashed_message = self::message_hash($message);
    
    $hash = str_replace('0x', '', $hashed_message);
    $private_key = str_replace('0x', '', $private_key);
    
    $secp256k1 = new Secp256k1();
    $signature = $secp256k1->sign($hash, $private_key);
    
    $r = gmp_strval($signature->getR(), 16);
    $s = gmp_strval($signature->getS(), 16);
    $recoveryParam = $signature->getRecoveryParam();
    
    $signature = self::encodeSignature($recoveryParam, $r, $s);
    
    return substr($signature, 0, 2) == '0x' ? $signature : ('0x' . $signature);
  }
  
  protected static function fromNat($val) {
    if ($val == '0x0') {
      return '0x';
    }
        
    if (strlen($val) % 2 === 0) {
      return $val;
    }
    
    return '0x0' . substr($val, 2);
  }
  
  protected static function pad($l, $hex) {
    if (strlen($hex) === ($l * 2 + 2)) {
      return $hex;
    }
    return self::pad($l, "0x0" . substr($hex, 2));
  }
  
  protected static function natFromString($str) {
      $bn = '0x';
      if (substr($str, 0, 2) == '0x') {
        $bn .= (new BN(substr($str, 2), 16))->toString("hex");
      } else {
        $bn .= (new BN($str, 10))->toString("hex");
      }
      
      return ($bn === "0x0") ? "0x" : $bn;
    }
  
  /**
   * encode signature according eth.account.sign
   */
  protected static function encodeSignature($v, $r, $s) {
    
    $r_nat = self::fromNat("0x" . $r);
    $s_nat = self::fromNat("0x" . $s);
    
    $combined_arr = array(
      self::pad(32, $r_nat),
      self::pad(32, $s_nat),
      self::natFromString(self::fromNumber(27 + $v))
    );
    
    $signature = '';
    
    for ($i = 0; $i < sizeof($combined_arr); $i++) {
      $signature .= substr($combined_arr[$i], 2);
    }
    
    return $signature;
  }
  
  /**
   * convert number to hex
   * @param $n - decimal number
   */
  protected static  function fromNumber($n) {
    $hex = dechex($n);
    if (strlen($hex) % 2 === 0) {
      return '0x' . $hex;
    }
    
    return '0x0' . $hex;
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
