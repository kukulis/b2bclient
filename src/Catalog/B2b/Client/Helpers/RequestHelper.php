<?php
/**
 * RequestHelper.php
 * Created by Giedrius Tumelis.
 * Date: 2021-04-13
 * Time: 09:05
 */

namespace Catalog\B2b\Client\Helpers;

use Catalog\B2b\Client\Exception\ClientAccessException;
use Catalog\B2b\Client\Exception\ClientErrorException;
use Catalog\B2b\Client\Exception\ClientSystemException;
use Catalog\B2b\Client\Exception\ClientValidateException;
use Catalog\B2b\Common\Data\Rest\ErrorResponse;

class RequestHelper
{
    const MAX_LOG_SIZE = 1024;

    /**
     * @param int $httpCode
     * @param string $reasonPhrase
     * @param string $contents
     * @throws ClientErrorException
     */
    public static function handleUnrecognizedResponse ( $httpCode, $reasonPhrase, $contents) {
        if ( $contents != null ) {
            $contents =substr($contents, 0, self::MAX_LOG_SIZE);
        }
        throw new ClientErrorException("Unrecognized response: ".$httpCode. '   Reason:'.$reasonPhrase. '   Content: '.$contents );
    }

    /**
     * @param ErrorResponse $response
     * @param int $httpCode
     * @throws ClientAccessException
     * @throws ClientErrorException
     * @throws ClientSystemException
     * @throws ClientValidateException
     */
    public static function responseToException(ErrorResponse $response, $httpCode) {
        if ($httpCode == 500) {
            throw new ClientErrorException($response->message);
        }
        if ($httpCode == 403) {
            throw new ClientAccessException($response->message);
        }
        if ($httpCode == 400) {
            throw new ClientValidateException($response->message);
        }
        if ($httpCode != 200) {
            throw new ClientSystemException($response->message);
        }
        throw new ClientErrorException($response->message);
    }
}