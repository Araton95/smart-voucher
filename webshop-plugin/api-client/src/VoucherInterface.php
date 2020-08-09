<?php

namespace SmartVoucher;

/**
 * VoucherInterface defines Voucher interface.
 */
interface VoucherInterface {
 
 /**
  * getId
  * @return integer or null
  */
 public function getId();

 /**
  * Set voucher id
  * @param int $id - voucher id 
  */
 public function setId(int $id);

 /**
  * getAmount - return voucher amount
  * @return float
  */
 public function getAmount();

 /**
  * setAmount - set voucher amount
  * @param float $amount - amount to set
  */
 public function setAmount(float $amount);


  /**
   * getWebshopAddress
   * @return string 
   */
 public function getWebshopAddr();

  /**
   * setWebshopAddress
   * @param string $address - address to set
   */
 public function setWebshopAddr(string $address);

  /**
   * getNonce - get voucher nonce
   * @return int
   */
 public function getNonce();

  /**
   * setNonce - set voucher nonce
   * @param int $nonce
   */
 public function setNonce(int $nonce);

 /**
  * getSignature - get Signature
  * @return string 
  */
 public function getSignature();

  /**
   * setSignature - sets voucher signature
   * @param string $signature
   */
 public function setSignature(string $signature);
 
  /**
   * getCreated - creation date of voucher
   * @return string
   */
 public function getCreated();
 
  /**
   * setCreated - sets voucher creation date
   * @param string $created
   */
 public function setCreated(string $created);
 
  /**
   * getUpdated - voucher updated date
   * @return string
   */
 public function getUpdated();
  
  /**
   * setUpdated - set voucher Updated date
   * @param string $updated
   */
 public function setUpdated(string $updated);

  /**
   * getInitialAmount - get voucher initial amount
   * @return float
   */
 public function getInitialAmount();
 
  /**
   * setInitialAmount - sets voucher initial amount
   * @param float $amount
   */
 public function setInitialAmount(float $amount);
 
  /**
   * getCurrentAmount - get voucher current amount
   * @return float 
   */
 public function getCurrentAmount();
 
  /**
   * setCurrentAmount - sets voucher current amount
   * @param float $amount
   */
 public function setCurrentAmount(float $amount);
 
}
