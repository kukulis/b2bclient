<?php

namespace Catalog\B2b\Client;

use Catalog\B2b\Client\Data\CategoriesRestResult;
use Catalog\B2b\Client\Data\LanguagesRestResult;
use Catalog\B2b\Client\Data\ParseResult;
use Catalog\B2b\Client\Data\ProductRestResult;
use Catalog\B2b\Client\Exception\ClientAccessException;
use Catalog\B2b\Client\Exception\ClientErrorException;
use Catalog\B2b\Client\Exception\ClientSystemException;
use Catalog\B2b\Client\Exception\ClientValidateException;
use Catalog\B2b\Client\Helpers\RequestHelper;
use Catalog\B2b\Common\Data\Catalog\Category;
use Catalog\B2b\Common\Data\Catalog\Language;
use Catalog\B2b\Common\Data\Rest\ErrorResponse;
use Catalog\B2b\Common\Data\Rest\RestResult;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JMS\Serializer\Exception\Exception as SerializeException;
use JMS\Serializer\Serializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sketis\PrekesBundle\Data\TmpPakuote;

class RestClient
{
    const LOCALE_PLACEHOLDER = 'LOCALE';
    const CATEGORY_CODE_PLACEHOLDER = 'CATEGORY_CODE';
    const CATEGORIES_ROOTS_URI = "/api/v3/categories_roots";
    const CATEGORIES_TREE_URI = "/api/v3/category_tree/CATEGORY_CODE/LOCALE";
    const CATEGORIES_LIST_URI = '/api/v3/categories/LANGUAGE';
    const LANGUAGES_LIST_URI = '/api/v3/languages';
    const PACKAGES_URI = '/api/v3/packages';
    const PRODUCTS_URI = '/api/v3/products/LOCALE';

    const ACCEPT_JSON = 'application/json';

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
     * @throws ClientAccessException
     * @throws ClientErrorException
     * @throws ClientSystemException
     * @throws ClientValidateException
     */
    public function getProducts(array $skus, $locale = 'en'): array
    {
        $endpoint = str_replace(self::LOCALE_PLACEHOLDER, $locale, self::PRODUCTS_URI);
        $requestUrl = $this->baseUrl . $endpoint;

        $headers = [
            'Accept' => self::ACCEPT_JSON,
            'Content-Type' => 'application/json'
        ];

        $body = json_encode($skus);


        try {
            $this->logger->error('RestClient::getProducts() request url: ' . $requestUrl);
            $response = $this->guzzle->request('post', $requestUrl, [
                'headers' => $headers,
                'body' => $body
            ]);
        } catch (GuzzleException $exception) {
            throw new ClientErrorException($exception->getMessage());
        }

        return $this->handleResponse($response, ProductRestResult::class);
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
    public function getCategoriesTree($rootCode, $locale)
    {
        $fixedLocaleUri = str_replace(self::LOCALE_PLACEHOLDER, $locale, self::CATEGORIES_TREE_URI);
        $fixedUri = str_replace(self::CATEGORY_CODE_PLACEHOLDER, $rootCode, $fixedLocaleUri);
        $url = $this->baseUrl . $fixedUri;

        $requestParams = [
            'headers' => ['Accept' => self::ACCEPT_JSON]
        ];

        $res = null;
        try {
            $res = $this->guzzle->request('post', $url, $requestParams);
        } catch (GuzzleException $e) {
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
    private function handleResponse(ResponseInterface $response, string $hintType)
    {
        $contents = $response->getBody()->getContents() ?? '';

        $parseResult = $this->parseResponse($contents, $hintType);

        if (isset($parseResult->restResult)) {
            return $parseResult->restResult->data;
        }

        if (isset($parseResult->errorResponse)) {
            RequestHelper::responseToException($parseResult->errorResponse, $response->getStatusCode());
        }

        $exceptionsMessages = array_map(
            function (Exception $e) {
                return $e->getMessage();
            },
            $parseResult->parseExceptions
        );
        $exceptionsMessagesStr = join(",", $exceptionsMessages);

        RequestHelper::handleUnrecognizedResponse($response->getStatusCode(), $exceptionsMessagesStr, $contents);

        return [];
    }

    /**
     * @param string $contents
     * @param string $hintType
     * @return ParseResult
     */
    public function parseResponse(string $contents, string $hintType)
    {
        $parseResult = new ParseResult();

        try {
            $parseResult->restResult = $this->serializer->deserialize($contents, $hintType, self::RESPONSE_FORMAT);
            return $parseResult;
        } catch (SerializeException $e) {
            $parseResult->parseExceptions[] = $e;
            $this->logger->info('Exception parsing response:' . $e->getMessage());
        }

        try {
            $parseResult->errorResponse = $this->serializer->deserialize(
                $contents,
                ErrorResponse::class,
                self::RESPONSE_FORMAT
            );
            return $parseResult;
        } catch (SerializeException $e) {
            $parseResult->parseExceptions[] = $e;
        }
        return $parseResult;
    }

    /**
     *
     * @return TmpPakuote[]
     */
    public function getPackages(?string $fromNomnr, int $limit = 500): array
    {
        $url = $this->baseUrl . self::PACKAGES_URI;
        $requestParams =
            [
                'headers' => ['Accept' => self::ACCEPT_JSON],
                'query' => [
                    'fromNomnr' => $fromNomnr,
                    'limit' => $limit,
                ]
            ];

        $res = $this->guzzle->request('get', $url, $requestParams);

        /** @var string[] $data */
        $data = $this->handleResponse($res, RestResult::class);

        /** @var TmpPakuote[] $tmpPakuotes */
        $tmpPakuotes = $this->serializer->fromArray($data, sprintf('array<%s>', TmpPakuote::class));

        return $tmpPakuotes;
    }

    /**
     * @return Category[]
     */
    public function getCategoriesList($lang, $offset, $limit): array
    {
        $url = $this->baseUrl . str_replace('LANGUAGE', $lang, self::CATEGORIES_LIST_URI);

        $requestParams =
            [
                'headers' => ['Accept' => self::ACCEPT_JSON],
                'query' => [
                    'offset' => $offset,
                    'limit' => $limit,
                ]
            ];

        $res = $this->guzzle->request('get', $url, $requestParams);

        /** @var Category[] $data */
        $data = $this->handleResponse($res, CategoriesRestResult::class);

        return $data;
    }

    public function getLanguagesList(): array
    {
        $url = $this->baseUrl . self::LANGUAGES_LIST_URI;

        $requestParams =
            [
                'headers' => ['Accept' => self::ACCEPT_JSON],
            ];

        $res = $this->guzzle->request('get', $url, $requestParams);

        /** @var Language[] $data */
        $data = $this->handleResponse($res, LanguagesRestResult::class);

        return $data;
    }
}