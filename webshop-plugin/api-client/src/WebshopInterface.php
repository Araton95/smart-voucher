<?php

namespace SmartVoucher;

/**
 * WebShop Interface
 */
interface WebshopInterface {
 
 /**
  * get webshop id
  * @return int
  */
  public function getId();

  /**
   * set webshop id
   * @param int $id
   */
  public function setId(int $id);
  
  /**
   * get wallet
   * @return string
   */
  public function getWallet();
  
  /**
   * set wallet
   * @param string $wallet
   */
  public function setWallet(string $wallet);

  /**
   * get webshop email
   * @return string
   */
  public function getEmail();
 
  /**
   * set webshop email
   * @param string $email
   */
  public function setEmail(string $email);

  /**
   * get webshop website
   * @return string
   */
  public function getWebsite();
  
  /**
   * set webshop website
   * @param string $website
   */
  public function setWebsite(string $website = "");

  /**
   * get webshop nonce
   * @return int
   */
  public function getNonce();
 
  /**
   * set webshop nonce
   * @param int $nonce
   */
  public function setNonce(int $nonce); 
  
  /**
   * get publishers
   * @return array of WebshopInterface
   */
  public function getPublishers();

  /**
   * set publishers
   * @param array $publishers - publishers list
   */
  public function setPublishers(array $publishers = []);

  /**
   * get subsribers
   * @return array of WebshopInterface
   */
  public function getSubscribers();
  
  /**
   * set subsribers
   * @param array of WebshopInterface
   */
  public function setSubscribers(array $subscribers = []);
  
  
  /**
   * get vouchers
   * @return array of VoucherInterface
   */
 public function getVouchers();
 
  /**
   * set vouchers
   * @param array of VoucherInterface
   */
 public function setVouchers(array $vouchers = []);
 
 /**
  * get vouchers count
  * @return int 
  */
  public function getVouchersCount();
  
  /**
   * set vouchers count
   * @param int $count
   */
  public function setVouchersCount(int $count);

  /**
   * add voucher
   * @param VoucherInterface $voucher
   */
  public function addVoucher(VoucherInterface $voucher);

  /**
   * add Publisher
   * @param WebshopInterface $publisher
   */
 public function addPublisher(WebshopInterface $publisher);

  /**
   * add Subscriber
   * @param WebshopInterface $subscriber
   */
  public function addSubscriber(WebshopInterface $subscriber);
}
