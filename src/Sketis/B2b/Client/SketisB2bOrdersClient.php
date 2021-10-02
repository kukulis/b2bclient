<?php

namespace Sketis\B2b\Client;

use Catalog\B2b\Client\Exception\ClientErrorException;
use Catalog\B2b\Client\Exception\ClientSystemException;
use Catalog\B2b\Client\Exception\ClientValidateException;
use Catalog\B2b\Client\Exception\ClientWarningException;
use GuzzleHttp\Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Sketis\B2b\Common\Data\Rest\ErrorResponse;
use Sketis\B2b\Common\Data\Rest\OrderStatus;
use Sketis\B2b\Common\Data\Rest\FindOrdersOfStatusesRestParams;
use Sketis\B2b\Common\Data\Rest\GetOrdersRequestParams;
use Sketis\B2b\Common\Data\Rest\Order3;
use Sketis\B2b\Common\Data\Rest\RestResults;
use Sketis\B2b\Common\Data\Rest\WrappedRestResults;

class SketisB2bOrdersClient
{
    const CLIENT_CODE_PLACEHOLDER = 'CLIENT_CODE';
    const LOCALE_PLACEHOLDER = 'LOCALE';

    const SEARCH_ORDERS_URI = "/api/ezp/v2/client/CLIENT_CODE/search_orders";
    const GET_ORDERS_URI = "/api/ezp/v2/client/CLIENT_CODE/get_orders";
    const REGISTER_ORDER_URI = "/api/ezp/v2/client/CLIENT_CODE/register_order";

    const ACCEPT_JSON = 'application/vnd.ez.api.Content+json';

    const BATCH_SIZE = 500;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Client */
    protected $guzzle;

    /** @var string */
    protected $baseUrl;

    /** @var Serializer */
    protected $serializer;

    /**
     * SketisB2bOrdersClient constructor.
     * @param LoggerInterface $logger
     * @param GuzzleHttpClient $guzzle
     * @param string $baseUrl
     * @param Serializer $serializer
     */
    public function __construct(
        LoggerInterface $logger,
        GuzzleHttpClient $guzzle,
        string $baseUrl,
        Serializer $serializer
    ) {
        $this->logger = $logger;
        $this->guzzle = $guzzle;
        $this->baseUrl = $baseUrl;
        $this->serializer = $serializer;
    }

    public function registerOrder($clientCode, Order3 $order3): array
    {
        $requestUri = $this->baseUrl . $this->fixClientCode(self::REGISTER_ORDER_URI, $clientCode);
        $jsonContent = $this->serializer->serialize($order3, 'json');
        try {
            $this->logger->info(
                self::class . '.registerOrder: requestUri: ' . $requestUri . ' jsonContent=' . $jsonContent
            );

            $res = $this->guzzle->post(
                $requestUri,
                [
                    'headers' => ['Accept' => self::ACCEPT_JSON],
                    'body' => $jsonContent
                ]
            );

            $contents = $res->getBody()->getContents();
            $this->logger->info(self::class . '.registerOrder: response contents: ' . $contents);

            /** @var WrappedRestResults $results */
            $results = $this->serializer->deserialize($contents, WrappedRestResults::class, 'json');
            $this->handleErrorResponse($results->results);
            $missingsArr = $results->results->data;

            $missingsMap = [];
            foreach ($missingsArr as $missingsLine) {
                $missingsMap[$missingsLine['nomnr']] = $missingsLine['amount'];
            }
            return $missingsMap;

//        } catch (InvalidArgumentException $iae) {
//            throw new SketisSystemException($iae->getMessage(), 0, $iae);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
            return [];
        }
    }

    /**
     * @return OrderStatus[]
     */
    public function searchOrders($clientCode, $statuses, $dateFrom, $offset = 0, $limit = 500): array
    {
        $params = new FindOrdersOfStatusesRestParams();
        $params->statuses = $statuses;
        $params->dateFrom = $dateFrom;
        $params->offset = $offset;
        $params->limit = $limit;

        $requestUri = $this->baseUrl . $this->fixClientCode(self::SEARCH_ORDERS_URI, $clientCode);
        $jsonContent = $this->serializer->serialize($params, 'json');

        try {
            $this->logger->info(
                self::class . '.searchOrders: requestUri: ' . $requestUri . ' jsonContent=' . $jsonContent
            );

            $res = $this->guzzle->post(
                $requestUri,
                [
                    'headers' => ['Accept' => self::ACCEPT_JSON],
                    'body' => $jsonContent
                ]
            );

            $contents = $res->getBody()->getContents();
            $this->logger->info(self::class . '.searchOrders: response contents: ' . $contents);

            /** @var WrappedRestResults $results */
            $results = $this->serializer->deserialize($contents, WrappedRestResults::class, 'json');
            $this->handleErrorResponse($results->results);
            $dataJson = json_encode($results->results->data);

            /** @var OrderStatus[] $orders */
            $orders = $this->serializer->deserialize($dataJson, 'array<' . OrderStatus::class . '>', 'json');
            return $orders;
//        } catch (InvalidArgumentException $iae) {
//            throw new SketisSystemException($iae->getMessage(), 0, $iae);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
            return [];
        }
    }

    /**
     * @return Order3[]
     */
    public function getOrders($clientCode, $ordersNumbers): array
    {
        $params = new GetOrdersRequestParams();
        $params->ordersNumbers = $ordersNumbers;

        $requestUri = $this->baseUrl . $this->fixClientCode(self::GET_ORDERS_URI, $clientCode);
        $jsonContent = $this->serializer->serialize($params, 'json');

        try {
            $this->logger->info(
                self::class . '.getOrders: requestUri: ' . $requestUri . ' jsonContent=' . $jsonContent
            );

            $res = $this->guzzle->post(
                $requestUri,
                [
                    'headers' => ['Accept' => self::ACCEPT_JSON],
                    'body' => $jsonContent
                ]
            );

            $contents = $res->getBody()->getContents();
            $this->logger->info(self::class . '.getOrders: response contents: ' . $contents);

            /** @var WrappedRestResults $results */
            $results = $this->serializer->deserialize($contents, WrappedRestResults::class, 'json');
            $this->handleErrorResponse($results->results);

            $ordersDataJson = json_encode($results->results->data);
            /** @var Order3[] $orders */
            $orders = $this->serializer->deserialize($ordersDataJson, 'array<' . Order3::class . '>', 'json');
            return $orders;
//        } catch (InvalidArgumentException $iae) {
//            throw new SketisSystemException($iae->getMessage(), 0, $iae);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
            return [];
        }
    }


    public function fixClientCode(string $uri, string $clientCode): string
    {
        return str_replace(self::CLIENT_CODE_PLACEHOLDER, $clientCode, $uri);
    }

    // TODO move to helper
    // TODO refactor code

    /**
     * @param ErrorResponse $errorResponse
     * @param string $info
     * @throws ClientErrorException
     * @throws ClientSystemException
     * @throws ClientValidateException
     * @throws ClientWarningException
     */
    public function handleErrorResponseStd($errorResponse, $info = '')
    {
        if (!isset($errorResponse->type) || !isset($errorResponse->message)) {
            return;
        }

        if ($errorResponse->type == ErrorResponse::TYPE_ERROR) {
            if (strpos($errorResponse->message, 'Server') !== false) {
                throw new ClientSystemException('Response err:' . $errorResponse->message . ' [' . $info . ']');
            }
            throw new ClientErrorException('Response err:' . $errorResponse->message . ' [' . $info . ']');
        } else {
            if ($errorResponse->type == ErrorResponse::TYPE_WARNING) {
                throw new ClientWarningException('Response err:' . $errorResponse->message . ' [' . $info . ']');
            } else {
                if ($errorResponse->type == ErrorResponse::TYPE_VALIDATION) {
                    throw new ClientValidateException(
                        'Response validate err:' . $errorResponse->message . ' [' . $info . ']'
                    );
                } else {
                    if ($errorResponse->type == ErrorResponse::TYPE_NOT_FOUND || $errorResponse->type == ErrorResponse::TYPE_SYSTEM) {
                        throw new ClientSystemException('Response err:' . $errorResponse->message . ' [' . $info . ']');
                    } else {
                        throw new ClientSystemException(
                            "Wrong response code:" . [$errorResponse->type] . " message:" . $errorResponse->message . ' [' . $info . ']'
                        );
                    }
                }
            }
        }
    }

    // TODO move to helper
    protected function handleErrorResponse(RestResults $responseObject, $info = '')
    {
        if ($responseObject->resultCode == RestResults::RESULT_ERROR) {
            throw new ClientErrorException('Response err:' . $responseObject->errorMessage . ' [' . $info . ']');
        } elseif ($responseObject->resultCode == RestResults::RESULT_WARNING) {
            throw new ClientWarningException('Response err:' . $responseObject->errorMessage . ' [' . $info . ']');
        } elseif ($responseObject->resultCode == RestResults::RESULT_VALIDATE) {
            throw new ClientValidateException(
                'Response validate err:' . $responseObject->errorMessage . ' [' . $info . ']'
            );
        } elseif ($responseObject->resultCode == RestResults::RESULT_SYSTEM) {
            throw new ClientSystemException('Response err:' . $responseObject->errorMessage . ' [' . $info . ']');
        } elseif (RestResults::RESULT_OK == $responseObject->resultCode) {
            $this->logger->debug('handleErrorResponse: Result is ok');
        } else {
            throw new ClientSystemException("Wrong response code:" . $responseObject->resultCode . ' [' . $info . ']');
        }
    }

    /**
     * @param RequestException $e
     * @param string $additionalInfo
     * @return string
     * @throws ClientErrorException
     */
    protected function handleRequestException(RequestException $e, $additionalInfo = '')
    {
        $this->logger->notice($e->getMessage());
        $response = $e->getResponse();
        $responseContents = null;
        if (!empty($response)) {
            $responseContents = $response->getBody()->getContents();
        }
        $this->logger->debug(self::class . '.handleRequestException: responseBody=' . $responseContents);
        try {
            $responseJsonObj = \GuzzleHttp\json_decode($responseContents);
            if (isset($responseJsonObj->error->exception[0]->message)) { //
                if (strpos($responseJsonObj->error->exception[0]->message, 'Document already exists') !== false) {
                    throw new ClientErrorException("Document already exists [$additionalInfo]");
                } else {
                    throw new ClientErrorException(
                        'Remote error:' . $responseJsonObj->error->exception[0]->message . ' [' . $additionalInfo . ']'
                    );
                }
            } else {
                if (isset($responseJsonObj->ErrorResponse)) {
                    $this->handleErrorResponseStd($responseJsonObj->ErrorResponse, $additionalInfo);
                }
            }
            throw new ClientErrorException ($responseContents);
        } catch (\InvalidArgumentException $ie) {
            throw new ClientErrorException($ie->getMessage());
        }
    }
}