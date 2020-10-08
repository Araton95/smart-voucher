<?php
namespace SmartVoucher;

interface PartnerRequestInterface {
  
  /**
   * get webshop address
   * @return string
   */
  public function getWebshopAddr();
  
  /**
   * set webshop address
   * @param string $addr
   */
  public function setWebshopAddr(string $addr);
  
  
  /**
   * get Nonce
   * @return int
   */
  public function getNonce();
  
  
  /**
   * set Nonce
   * @param int $nonce
   */
  public function setNonce(int $nonce);
  
  /**
   * get signature
   * @return string
   */
  public function getSignature();
  
  /**
   * set signature
   * @param string $signature
   */
  public function setSignature(string $signature);
  
  
}
