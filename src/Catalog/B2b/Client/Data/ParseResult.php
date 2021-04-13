<?php
/**
 * ParseResult.php
 * Created by Giedrius Tumelis.
 * Date: 2021-04-13
 * Time: 09:40
 */

namespace Catalog\B2b\Client\Data;


use Catalog\B2b\Common\Data\Rest\ErrorResponse;
use Catalog\B2b\Common\Data\Rest\RestResult;

class ParseResult
{
    /** @var RestResult */
    public $restResult;

    /** @var ErrorResponse */
    public $errorResponse;

    /** @var \JMS\Serializer\Exception\Exception[] */
    public $parseExceptions=[];
}