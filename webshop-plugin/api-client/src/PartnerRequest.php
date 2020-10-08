<?php

namespace SmartVoucher;

/**
 * Class PartnerRequest
 */
 
class PartnerRequest implements PartnerRequestInterface {
  
  /**
   * webshopAddr
   */
  protected $webshopAddr;
  
  /**
   * nonce
   */
  protected $nonce;
  
  /**
   * signature
   */
  protected $signature;

  /**
   * {@inheritdoc}
   */
  public function getWebshopAddr() {
    return $this->webshopAddr; 
  }
  
  /**
   * {@inheritdoc}
   */
  public function setWebshopAddr(string $addr) {
    $this->webshopAddr = $addr;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getNonce() {
    return $this->nonce;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setNonce(int $nonce) {
    $this->nonce = $nonce;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getSignature() {
    return $this->signature;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setSignature(string $signature) {
    $this->signature = $signature;
    return $this; 
  }
}
