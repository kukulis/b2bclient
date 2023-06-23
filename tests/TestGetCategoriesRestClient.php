<?php

namespace Catalog\B2b\Client\Tests;

use Catalog\B2b\Client\RestClient;
use Catalog\B2b\Client\Test\SimpleLogger;
use Catalog\B2b\Common\Data\Catalog\Category;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

class TestGetCategoriesRestClient
    extends TestCase
{
    public function testGetPackages()
    {
        $guzzle = new Client();
        $logger = new SimpleLogger();
        $baseUrl = 'http://catalog_web';

        $serializer = SerializerBuilder::create()->build();

        $restClient = new RestClient($logger, $guzzle, $baseUrl, $serializer);


        $categories = $restClient->getCategoriesList('lt', 0, 10 );
        $this->assertCount(10, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
    }
}