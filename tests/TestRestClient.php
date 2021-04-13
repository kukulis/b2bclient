<?php
/**
 * TestRestClient.php
 * Created by Giedrius Tumelis.
 * Date: 2021-04-12
 * Time: 16:22
 */

namespace Tests;


use Catalog\B2b\Client\RestClient;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

class TestRestClient extends TestCase
{
    public function testGetRoots() {
        $guzzle = new Client();
        $logger = new SimpleLogger();
        $baseUrl = 'http://gtcatalog.dv';

        $serializer = SerializerBuilder::create()->build();

        $restClient = new RestClient($logger, $guzzle, $baseUrl, $serializer);
        $catRoots = $restClient->getCategoriesRoots();
        $this->assertGreaterThan(0, count($catRoots));

        var_dump ( $catRoots );
    }


    public function testGetTree() {
        $guzzle = new Client();
        $logger = new SimpleLogger();
        $baseUrl = 'http://gtcatalog.dv';

        $serializer = SerializerBuilder::create()->build();

        $restClient = new RestClient($logger, $guzzle, $baseUrl, $serializer);
        $categories = $restClient->getCategoriesTree('accessories', 'lt');
        $this->assertGreaterThan(0, count($categories));
        var_dump ( $categories );
    }
}