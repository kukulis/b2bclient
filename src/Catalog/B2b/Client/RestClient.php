<?php
/**
 * RestClient.php
 * Created by Giedrius Tumelis.
 * Date: 2021-04-12
 * Time: 16:21
 */

namespace Catalog\B2b\Client;


use Catalog\B2b\Client\Data\CategoriesRestResult;
use Catalog\B2b\Client\Data\ParseResult;
use Catalog\B2b\Client\Exception\ClientAccessException;
use Catalog\B2b\Client\Exception\ClientErrorException;
use Catalog\B2b\Client\Exception\ClientSystemException;
use Catalog\B2b\Client\Exception\ClientValidateException;
use Catalog\B2b\Client\Helpers\RequestHelper;
use Catalog\B2b\Common\Data\Catalog\Category;
use Catalog\B2b\Common\Data\Rest\ErrorResponse;
use Catalog\B2b\Common\Data\Rest\RestResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

use JMS\Serializer\Serializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;


use \JMS\Serializer\Exception\Exception as SerializeException;

use \Exception;


class RestClient
{
    const LOCALE_PLACEHOLDER='LOCALE';
    const CATEGORY_CODE_PLACEHOLDER = 'CATEGORY_CODE';
    const CATEGORIES_ROOTS_URI = "/api/v3/categories_roots";
    const CATEGORIES_TREE_URI = "/api/v3/category_tree/CATEGORY_CODE/LOCALE";

    const ACCEPT_JSON='application/json';

    const RESPONSE_FORMAT = 'json';

    /** @var LoggerInterface */
    protected $logger;

    /** @var Client */
    protected $guzzle;

    /** @var string */
    protected $baseUrl;

    /** @var Serializer */
    protected $serializer;

    /**
     * RestClient constructor.
     * @param LoggerInterface $logger
     * @param Client $guzzle
     * @param string $baseUrl
     * @param Serializer $serializer
     */
    public function __construct(LoggerInterface $logger, Client $guzzle, string $baseUrl, Serializer $serializer)
    {
        $this->logger = $logger;
        $this->guzzle = $guzzle;
        $this->baseUrl = $baseUrl;
        $this->serializer = $serializer;
    }


    /**
     * @return string[]
     * @throws ClientAccessException
     * @throws ClientErrorException
     * @throws ClientSystemException
     * @throws ClientValidateException
     */
    public function getCategoriesRoots() {
        $url = $this->baseUrl.self::CATEGORIES_ROOTS_URI;
        $requestParams =
            [
                'headers' => ['Accept' => self::ACCEPT_JSON]
            ];

        $res = null;
        try {
            $res = $this->guzzle->request('get', $url, $requestParams);
        } catch ( GuzzleException $e) {
            throw new ClientErrorException($e->getMessage());
        }

        $hintType = RestResult::class;

        /** @var string[] $data */
        $data = $this->handleResponse($res, $hintType);
        return $data;
    }

    /**
     * @param string $rootCode
     * @param string $locale
     * @return Category[]
     * @throws ClientErrorException
     * @throws ClientAccessException
     * @throws ClientSystemException
     * @throws ClientValidateException
     */
    public function getCategoriesTree($rootCode, $locale) {
        $fixedLocaleUri = str_replace(self::LOCALE_PLACEHOLDER, $locale, self::CATEGORIES_TREE_URI);
        $fixedUri = str_replace(self::CATEGORY_CODE_PLACEHOLDER, $rootCode, $fixedLocaleUri);
        $url = $this->baseUrl.$fixedUri;

        $requestParams = [
                'headers' => ['Accept' => self::ACCEPT_JSON]
            ];

        $res = null;
        try {
            $res = $this->guzzle->request('post', $url, $requestParams);
        } catch ( GuzzleException $e) {
            throw new ClientErrorException($e->getMessage());
        }

        $hintType = CategoriesRestResult::class;
        /** @var Category[] $data */
        $data = $this->handleResponse($res, $hintType);
        return $data;
    }

    /**
     * @param ResponseInterface $response
     * @param string $hintType
     * @return array
     * @throws ClientErrorException
     * @throws ClientAccessException
     * @throws ClientSystemException
     * @throws ClientValidateException
     */
    private function handleResponse(ResponseInterface  $response, string $hintType) {
        $contents = '';
        if ( $response->getBody() != null ) {
            $contents = $response->getBody()->getContents();
        }

        $parseResult = $this->parseResponse($contents, $hintType);

        if ( $parseResult->restResult != null ) {
            return $parseResult->restResult->data;
        }

        // -- remaining error handling ---
        if ($parseResult->errorResponse != null ) {
            RequestHelper::responseToException($parseResult->errorResponse, $response->getStatusCode());
        }

        $exceptionsMessages = array_map ( function (Exception $e) {return $e->getMessage();},   $parseResult->parseExceptions);
        $exceptionsMessagesStr = join ( ",", $exceptionsMessages );

        // in this place means, that response was unrecognized
        RequestHelper::handleUnrecognizedResponse($response->getStatusCode(), $exceptionsMessagesStr, $contents);
        return [];
    }

    /**
     * @param string $contents
     * @param string $hintType
     * @return ParseResult
     */
    public function parseResponse(string $contents, string $hintType) {
        $parseResult = new ParseResult();

        try {
            $parseResult->restResult = $this->serializer->deserialize($contents, $hintType, self::RESPONSE_FORMAT);
            return $parseResult;
        }
        catch (SerializeException $e ) {
            $parseResult->parseExceptions[] = $e;
            $this->logger->info( 'Exception parsing response:'. $e->getMessage());
        }

        try {
            $parseResult->errorResponse = $this->serializer->deserialize($contents, ErrorResponse::class, self::RESPONSE_FORMAT );
            return $parseResult;
        }
        catch (SerializeException $e) {
            $parseResult->parseExceptions[] = $e;
        }
        return $parseResult;
    }
}