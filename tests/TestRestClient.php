<?php

namespace Catalog\B2b\Client\Tests;

use Catalog\B2b\Client\RestClient;
use Catalog\B2b\Client\Test\SimpleLogger;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

class TestRestClient extends TestCase
{
    public function testGetRoots() {
        $guzzle = new Client();
        $logger = new SimpleLogger();
        $baseUrl = 'http://catalog_web';

        $serializer = SerializerBuilder::create()->build();

        $restClient = new RestClient($logger, $guzzle, $baseUrl, $serializer);
        $catRoots = $restClient->getCategoriesRoots();
        $this->assertGreaterThan(0, count($catRoots));

        var_dump ( $catRoots );
    }


    public function testGetTree() {
        $guzzle = new Client();
        $logger = new SimpleLogger();
        $baseUrl = 'http://catalog_web';

        $serializer = SerializerBuilder::create()->build();

        $restClient = new RestClient($logger, $guzzle, $baseUrl, $serializer);
        $categories = $restClient->getCategoriesTree('accessories', 'lt');
        $this->assertGreaterThan(0, count($categories));
        var_dump ( $categories );
    }

}