<?php

namespace SmartVoucher;

/**
 * Redeem Voucher Interface
 */
interface RedeemVoucherInterface {
  
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
   * get redeem amount
   * @return float
   */
  public function getAmount();
  
  /**
   * set amount
   * @param float $amount
   */
  public function setAmount(float $amount);
  
  /**
   * get id
   * @return int
   */ 
  public function getId();
  
  /**
   * set id
   * @param int $id
   */
  public function setId(int $id);
  
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

