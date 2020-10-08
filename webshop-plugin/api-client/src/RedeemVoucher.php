<?php

namespace SmartVoucher;

/**
 * class RedeemVoucher
 */

class RedeemVoucher implements RedeemVoucherInterface {
  
  /**
   * voucher id
   */
  protected $id;
  
  /**
   * webshopAddr
   */
  protected $webshopAddr;
  
  /**
   * amount to redeem
   */
  protected $amount;
  
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
  public function getId() {
    return $this->id;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setId(int $id) {
    $this->id = $id;
    return $this;
  }
  
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
  public function getAmount() {
    return $this->amount;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setAmount(int $amount) {
    $this->amount = $amount;
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

  /**
   * Create RedeemVoucher from array().
   * @return false or RedeemVoucher
   */
  public static function createFromArray(array $values) {
    if (empty($values))
      return false;
    
    $voucher = new self();
    
    foreach ($values as $key => $value) {
      switch ($key) {
        case 'id':
          $voucher->setId($value);
        break;
        case 'webshopAddr':
          $voucher->setWebshopAddr($value);
        break;
        case 'signature':
          $voucher->setSignature($value);
        break;
        case 'amount':
          $voucher->setAmount($value);
        break;
        case 'nonce':
          $voucher->setNonce($value);
        break;
      }
    }
    
    return $voucher;
  }

}
