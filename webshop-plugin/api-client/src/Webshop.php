<?php

namespace SmartVoucher;

/**
 * class Webshop
 */
class Webshop implements WebshopInterface {

 /**
  * webshop id
  */  
 protected $id;

 /**
  * webshop wallet
  */
 protected $wallet;

 /**
  * webshop website
  */  
 protected $website;

 /**
  * webshop publishers
  */
 protected $publishers;

 /**
  * webshop subscribers
  */
 protected $subscribers;

 /**
  * webshop vouchers
  */
 protected $vouchers;

 /**
  * nonce
  */
 protected $nonce;
 
  /**
   * email
   */
 protected $email;

  public function __construct() {
    $this->publishers = [];
    $this->subscribers = [];
    $this->vouchers = [];
  }
  
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
  public function getWallet() {
    return $this->wallet;
  }

  /**
   * {@inheritdoc}
   */
  public function setWallet(string $wallet) {
    $this->wallet = $wallet;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebsite() {
    return $this->website;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setWebsite(string $website = '') {
    $this->website = $website;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail(string $email) {
    $this->email = $email;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublishers() {
    return $this->publishers;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublishers(array $publishers = []) {
    $this->publishers = $publishers;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribers() {
   return $this->subscribers;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubscribers(array $subscribers = []) {
    $this->subscribers = $subscribers;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVouchers() {
    return $this->vouchers;
  }

  /**
   * {@inheritdoc}
   */
  public function setVouchers(array $vouchers = []) {
    $this->vouchers = $vouchers;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addVoucher(VoucherInterface $voucher) {
    $this->vouchers[] = $voucher;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function addSubscriber(WebshopInterface $subscriber) {
    $this->subscribers[] = $subscriber;
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function addPublisher(WebshopInterface $publisher) {
    $this->publishers[] = $publisher;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVouchersCount() {
    return $this->vouchersCount;
  }
  
  /**
   * {@inheritdoc}
   */
  public function setVouchersCount(int $count) {
    $this->vouchersCount = $count;
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
   * static method to create Webshop from array
   * @param array properties with values
   * @return WebshopInterface $webshop
   */
  public static function createFromArray(array $item) {
    $webshop = new self();
    foreach ($item as $key => $value) 
    {
      switch ($key) 
      {
        case 'id':
         $webshop->setId($value);
        break;
        case 'wallet':
          $webshop->setWallet($value);
        break;
        case 'website':
          $webshop->setWebsite($value);
        break;
        case 'nonce':
          $webshop->setNonce($value);
        break;
        case 'vouchersCount':
          $webshop->setVouchersCount($value);
        break;
        case 'email':
          $webshop->setEmail($value);
        break;
      }
    }
    
    if (!empty($item['publishers'])) 
    {
      foreach ($item['publishers'] as $publisher_item) 
      {
        $webshop->addPublisher(Webshop::createFromArray($publisher_item));
      }
    }
    
    if (!empty($item['subscribers'])) 
    {
      foreach ($item['subscribers'] as $subscriber_item) 
      {
        $webshop->addSubscriber(Webshop::createFromArray($subscriber_item));
      }
    }
    
    if (!empty($item['vouchers'])) 
    {
      foreach ($item['vouchers'] as $voucher_item) 
      {
        $webshop->addVoucher(Voucher::createFromArray($voucher_item));
      }
    }
    return $webshop;
  }
}
