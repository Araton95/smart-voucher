<?php

namespace SmartVoucher;

use GuzzleHttp\Client as GuzzleСlient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 * class Client communication interface with real api
 */
class Client {
  
  /**
   * @see GuzzleHttp\Client
   */
  private $client;
  
  /**
   * api base url
   */
  const BASE_URL = 'https://pumacy-vm2.westeurope.cloudapp.azure.com';
  
  /**
   * you can pass additional params to guzzle client via 'client' key
   *
   */
  public function __construct(array $params=[]) 
  {
    
    $params['client'] = isset($params['client']) ? $params['client'] : [];
    $client_params = array_merge(['base_uri' => self::BASE_URL], $params['client']);
    $this->client = new GuzzleСlient($client_params);
  }
  
  /**
   * Helper function to check do we have an error or not
   * if we have than return errorMessage and a code
   * otherwise bool
   */
  private function hasError($data) {
    $error = null;
    $message = null;
    if (!empty($data['error'])) {
      return [
        'error' => $data["statusCode"],
        'message' => array_shift($data["message"]["message"]),
      ];
    }
    return false;
  }
  
  /**
   * Retrieve all webshops
   * @returns array - array of WebshopInterface type
   */
  public function getWebshops() 
  {
    try {
     $response = $this->client->request('GET', '/webshops');
     $webshops = [];
     foreach(json_decode($response->getBody()->getContents(), true) as $item) {
      $webshops[] = Webshop::createFromArray($item);
     }
     return $webshops;
    } catch (ConnectException | RequestException $e) {
      throw new \Exception ($e->getMessage());
    }
    
    return  [];
  }
  
  /**
   * Retrieve webshop details
   * @param int $id
   * @return WebshopInterface
   */
  
  public function getWebshop($id) {
    try {
      $response = $this->client->request('GET', '/webshops/' . $id);
      $data = json_decode($response->getBody()->getContents(), true);
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      return Webshop::createFromArray($data);
    } catch (ConnectException | RequestException $e) {
      throw new SmartVoucherException ($e->getMessage(), $e->getCode());
    }
  }
  
  /**
   * Register webshop
   * @param WebshopInterface $webshop
   */
  public function registerWebshop(WebshopInterface $webshop) {
    try {
      $response = $this->client->request('POST', '/webshops', [
        'wallet' => $webshop->getWallet(),
        'website' => $webshop->getWebsite(),
        'email' => $webshop->getEmail(),
      ]);
      
      $data = json_decode($response->getBody()->getContents(), true);
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      $webshop->setId($data['id']);
    } catch (ConnectException | RequestException $e) {
      throw new SmartVoucherException($e->getMessage(), $e->getCode());
    }
  }
  
  /**
   * Add Partner
   * @param WebshopInterface $requester - webshop that want to add partner
   * @param WebshopInterface $requestee - webshop to add as partner
   * @return bool
   */
  public function addPartner(WebshopInterface $requester, WebshopInterface $requestee) {
    try {
      $response = $this->client->request('POST', '/webshops/addPartner', [
        'webshopAddr' => $requester->getWallet(),
        'partnerAddr' => $requestee->getWallet(),
        'nonce' => $requester->getNonce(),
        'signature' => $requester->getSignature(),
      ]);
      
      $data = json_decode($response->getBody()->getContents(), true)
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
    } catch (ConnectException | RequestException $e) {
      throw new SmartVoucherException($e->getMessage(), $e->getCode());
    }
    return false;
  }
  
  /**
   * Remove Partner
   * @param WebshopInterface $requester - webshop that want to remove partner
   * @param WebshopInterface $requestee - Partner webshop that will be removed
   * @return bool
   */
  public function removePartner(WebshopInterface $requester, WebshopInterface $requestee) {
    try {
      $response = $this->client->request('POST', '/webshops/removePartner', [
        'webshopAddr' => $requester->getWallet(),
        'partnerAddr' => $requestee->getWallet(),
        'nonce' => $requester->getNonce(),
        'signature' => $requester->getSignature(),
      ]);
      
      $data = json_decode($response->getBody()->getContents(), true)
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      
    } catch (ConnectException | RequestException $e) {
      throw new \Exception($e->getMessage());
    }
    
    return true;
  }
  
  /**
   * Redeem Voucher
   * @param RedeemVoucherInterface $voucher
   */
  public function redeemVoucher(RedeemVoucherInterface $voucher) {
    try {
      $response = $this->client->request('POST', '/vouchers/redeem', [
        'webshopAddr' => $requester->getWebshopAddr(),
        'amount' => $voucher->getAmount(),
        'voucherId' => $voucher->getId(),
        'nonce' => $voucher->getNonce(),
        'signature' => $voucher->getSignature(),
      ]);
      
      $data = json_decode($response->getBody()->getContents(), true)
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
    } catch (ConnectException | RequestException $e) {
      throw new \Exception($e->getMessage());
    }
  }
  
  /**
   * Create voucher
   * @param VoucherInterface $voucher
   * @return 
   */
  public function createVoucher(VoucherInterface $voucher) {
    try {
      $response = $this->client->request('POST', '/vouchers', [
        'webshopAddr' => $requester->getWebshopAddr(),
        'amount' => $voucher->getAmount(),
        'nonce' => $voucher->getNonce(),
        'signature' => $voucher->getSignature(),
      ]);
      $data = json_decode($response->getBody()->getContents(), true)
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      
    } catch (ConnectException | RequestException $e) {
      throw new \Exception($e->getMessage());
    }
  }
  
  /**
   * Validate Voucher code
   * @param string $code
   * @return bool
   */
  public function validateVoucherCode(string $code) {
    try {
      $response = $this->client->request('GET', '/vouchers', [
        'code' => $code,
      ]);
      
      $data = json_decode($response->getBody()->getContents(), true)
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
    } catch (ConnectException | RequestException $e) {
      throw new \Exception($e->getMessage());
    }
    
    return false;
  }
}