<?php

namespace SmartVoucher;

use GuzzleHttp\Client as GuzzleСlient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use kornrunner\Solidity;

/**
 * class Client communication interface with real api
 */
class Client {
  
  private $private_key;
  
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
  
  
  public function setPrivateKey($key) {
    $this->private_key = $key;
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
      } else {
        return Webshop::createFromArray($data);
      }
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
      $post_data = [
        'wallet' => $webshop->getWallet(),
        'website' => $webshop->getWebsite(),
        'email' => $webshop->getEmail(),
       ];
      $response = $this->client->request('POST', '/webshops', [
        'json' => $post_data
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
   * @param PartnerRequestInterface $requester - webshop that want to add partners
   * @param mix $requestee - webshop to add as partner
   * @return bool
   */
  public function addPartner(PartnerRequestInterface $requester, array $requestee_wallet) {
    
    if (is_string($requestee_wallet))
      $requestee_wallet = array($requestee_wallet);
    
    $requestee_wallet = array_values($requestee_wallet);
    
    $signature_hash = Solidity::sha3($requestee_wallet[0], (string) $requester->getNonce());
    $signature = Util::sign($signature_hash, $this->private_key);
    
    $params = [
      'webshopAddr' => $requester->getWebshopAddr(),
      'partners' => $requestee_wallet,
      'nonce' => sprintf("%s", $requester->getNonce()),
      'signature' => $signature,
    ];
    try {
      $response = $this->client->request('POST', '/webshops/addPartners', [
        'json' => $params
        ]);
      
      $data = json_decode($response->getBody()->getContents(), true);
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      return $data['ok'];
    } catch (ConnectException | RequestException $e) {
      throw new SmartVoucherException($e->getMessage(), $e->getCode());
    }
    return false;
  }
  
  /**
   * Remove Partner
   * @param PartnerRequestInterface $requester - webshop that want to remove partner
   * @param array $requestee - Partner wallet that will be removed
   * @return bool
   */
  public function removePartner(PartnerRequestInterface $requester, array $requestee_wallet) {
    $requestee_wallet = array_values($requestee_wallet);
    
    $signature_hash = Solidity::sha3($requestee_wallet[0], $requester->getNonce());
    $signature = Util::sign($signature_hash, $this->private_key);
    
    $post_data = [
        'webshopAddr' => $requester->getWebshopAddr(),
        'partners' => $requestee_wallet,
        'nonce' => sprintf("%s", $requester->getNonce()),
        'signature' => $signature,
      ];
    try {
      $response = $this->client->request('POST', '/webshops/removePartners', [
        'json' => $post_data]);
      
      $data = json_decode($response->getBody()->getContents(), true);
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      return $data['ok'];
    } catch (ConnectException | RequestException $e) {
      throw new \Exception($e->getMessage());
    }
  }
  
  /**
   * Redeem Voucher
   * @param RedeemVoucherInterface $voucher
   */
  public function redeemVoucher(RedeemVoucherInterface $voucher) {
    
    $signature_hash = Solidity::sha3((string) $voucher->getAmount(), (string) $voucher->getId(),(string) $voucher->getNonce());
    $signature = Util::sign($signature_hash, $this->private_key);
    $params = [
      'webshopAddr' => $voucher->getWebshopAddr(),
      'amount' => $voucher->getAmount(),
      'voucherId' => $voucher->getId(),
      'nonce' => sprintf("%s", $voucher->getNonce()),
      'signature' => $signature,
     ];

    try {
      $response = $this->client->request('POST', '/vouchers/redeem', [
        'json' => $params
       ]);
      
      $data = json_decode($response->getBody()->getContents(), true);

      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      return $data;
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
    $data = [];
    
    $signature_hash = Solidity::sha3((string) $voucher->getAmount(), (string) $voucher->getNonce());
    $signature = Util::sign($signature_hash, $this->private_key);
    $voucher->setSignature($signature);
    try {
      $request_params = [
        'webshopAddr' => $voucher->getWebshopAddr(),
        'amount' => $voucher->getAmount(),
        'nonce' => sprintf('%s', $voucher->getNonce()),
        'signature' => $signature,
      ];
      
      $response = $this->client->request('POST', '/vouchers', [
        'json' => $request_params
      ]);
      $data = json_decode($response->getBody()->getContents(), true);
      
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      } else {
        if ($data['ok'] == 1) {
           $voucher->setVoucherCode($data['voucherCode']);
        }
      }
      
    } catch (ConnectException | RequestException $e) {
      throw new \SmartVoucherException($e->getMessage(), $e->getCode());
    }
    
    return $data;
  }
  
  /**
   * get Voucher details by id
   *
   */
  public function getVoucher($id) {
    try {
      $response = $this->client->request('GET', '/vouchers/' . $id);
      $data = json_decode($response->getBody()->getContents(), true);
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      
      return Voucher::createFromArray($data);
    } catch (ConnectException | RequestException $e) {
       throw new SmartVoucherException($e->getMessage(), $e->getCode());
    }
  }
  
  /**
   * Validate Voucher code
   * @param string $webshopAddr
   * @param string $code
   * @return bool
   */
  public function validateVoucherCode(string $webshopAddr, string $code) {
    try {
      $response = $this->client->request('GET', '/vouchers/validateCode', [
        'query' => ['webshopAddr' => $webshopAddr,'voucherCode' => $code],
      ]);
      
      $data = json_decode($response->getBody()->getContents(), true);
      if (($error = $this->hasError($data))) {
        throw new SmartVoucherException($error['message'], $error["code"]);
      }
      return $data['voucherId'];
    } catch (ConnectException | RequestException $e) {
      throw new \Exception($e->getMessage());
    }
    
    return false;
  }
}
