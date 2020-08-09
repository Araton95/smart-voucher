<?php

use PHPUnit\Framework\TestCase;

use SmartVoucher\Client;
use SmartVoucher\VoucherInterface;
use SmartVoucher\WebshopInterface;
use SmartVoucher\SmartVoucherException;
use SmartVoucher\Webshop;

final class GetallwebshopsTest extends TestCase {
  
  public function testCanGetAllWebshops(): array
  {
    $client = new Client();
    $webshops = $client->getWebshops();
    $this->assertIsArray($webshops);
    return [$webshops];
  }
  
  /** 
   * @dataProvider testCanGetAllWebshops
   */
  public function testCanGetAllWebShopsInsanceOfWebshop($item): void
  {
    $this->assertInstanceOf(WebshopInterface::class, $item, new Exception);
  }
  
  public function testCanGetWebShopById()
  {
    $client = new Client();
    $webshop = $client->getWebshop('0xa7DD6E42EF9728DBE4793481b9b8E0703542a786');
    $this->assertInstanceOf(WebshopInterface::class, $webshop, new \Exception);
    $this->assertNotEmpty($webshop->getId());
    $this->assertSame(1, $webshop->getId(), new \Exception("Id is not same"));
    $this->assertSame("0xa7DD6E42EF9728DBE4793481b9b8E0703542a786", $webshop->getWallet(), new \Exception("Wrong wallet"));
    $this->assertSame("www.amazon.com", $webshop->getWebsite(), new \Exception("Wrong website"));
    $this->assertSame(7, $webshop->getNonce(), new \Exception("Wrong nonce "));
    $this->assertSame(3, $webshop->getVouchersCount(), new \Exception("Wrong voucher count"));
  }
  
  public function testCanGetWebShopByWrongId()
  {
    $this->expectException(SmartVoucherException::class);
    $client = new Client();
    $client->getWebshop('0xa7DD6E42EF9728DBE4793481b9b8E0703542a781');
  }
  
  public function testRegisterWebshop() {
    $client = new Client();
    $webshop = Webshop::createFromArray([
      'website' => 'www.websystems.am',
      'wallet' => '0xa7DD6E42EF9728DBE4793481b9b8E0703542a786',
      'email' => 'test@websystems.am'
     ]);
    $client->registerWebshop($webshop);
  }
  
}
