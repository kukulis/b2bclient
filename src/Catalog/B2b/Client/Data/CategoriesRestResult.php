<?php

namespace Catalog\B2b\Client\Data;

use Catalog\B2b\Common\Data\Rest\RestResult;
use JMS\Serializer\Annotation as Serializer;

class CategoriesRestResult extends RestResult
{
    /**
     * @var string
     * @Serializer\Type("array<Catalog\B2b\Common\Data\Catalog\Category>")
     */
    public $data=[];
}