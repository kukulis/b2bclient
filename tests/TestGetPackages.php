<?php

namespace Catalog\B2b\Client\Tests;

use Catalog\B2b\Client\RestClient;
use Catalog\B2b\Client\Test\SimpleLogger;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use Sketis\PrekesBundle\Data\TmpPakuote;

class TestGetPackages extends TestCase
{
    public function testGetPackages() {
        $guzzle = new Client();
        $logger = new SimpleLogger();
        $baseUrl = 'http://catalog_web';

        $serializer = SerializerBuilder::create()->build();

        $restClient = new RestClient($logger, $guzzle, $baseUrl, $serializer);


        $packages = $restClient->getPackages('', 5);
        $this->assertCount(5, $packages);
        $this->assertInstanceOf(TmpPakuote::class, $packages[0] );
    }
}