<?php

namespace SmartVoucher;

/**
 * class Voucher
 * real implementation of VoucherInterface
 */
class Voucher implements VoucherInterface {
  
  /**
   * id of voucher
   */
  protected $id;
  
  /**
   * voucher amount
   */
  protected $amount;
  
  /**
   * voucher webshopAddr
   */
  protected $webshopAddr;
  
  /**
   * voucher nonce
   */
  protected $nonce;
  
  /**
   * voucher signature
   */
  protected $signature;
  
   /**
    * voucher initialAmount
    */
  protected $initialAmount;
  
   /**
    * voucher currentAmount
    */
  protected $currentAmount;
  
   /**
    * voucher creation date
    */
  protected $created_at;
  
   /**
    * voucher change date
    */
  protected $updated_at;
  
  /**
   * voucher code
   */
  protected $voucherCode;
  
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
  public function getAmount() {
    return $this->amount;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(float $amount) {
    $this->amount = $amount;
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
  public function setWebshopAddr(string $webshopAddr) {
    $this->webshopAddr = $webshopAddr;
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
   * {@inheritdoc}
   */
  public function getInitialAmount() {
    return $this->initialAmount;
  }

  /**
   * {@inheritdoc}
   */
  public function setInitialAmount(float $amount) {
    $this->initialAmount = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentAmount() {
    return $this->currentAmount;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentAmount(float $amount) {
    $this->currentAmount = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    return $this->created_at;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreated(string $created) {
    $this->created_at = $created;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getUpdated() {
    return $this->updated_at;
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdated(string $updated) {
    $this->updated_at = $updated;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getVoucherCode() {
    return $this->voucherCode;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setVoucherCode(string $code) {
    $this->voucherCode = $code;
    return $this;
  }
  
  
  /**
   * static function to create Voucher from array
   */
  public static function createFromArray(array $item) {
    $voucher = new self();
    foreach ($item as $key => $value) 
    {
      switch ($key)
      {
        case 'id':
          $voucher->setId($value);
        break;
        case 'amount':
          $voucher->setAmount($value);
        break;
        case 'webshopAddr':
          $voucher->setWebshopAddr($value);
        break;
        case 'nonce':
          $voucher->setNonce($value);
        break;
        case 'signature':
          $voucher->setSignature($value);
        break;
        case 'initialAmount':
          $voucher->setInitialAmount($value);
        case 'currentAmount':
          $voucher->setCurrentAmount($value);
        break;
        case 'created_at':
          $voucher->setCreated($value);
        break;
        case 'updated_at':
          $voucher->setUpdated($value);
        break;
        case 'voucherCode':
          $voucher->setVoucherCode($value); 
        break;
      }
    }
    return $voucher;
  }
  
}
