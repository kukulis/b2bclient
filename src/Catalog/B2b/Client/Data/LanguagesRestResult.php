<?php

namespace Catalog\B2b\Client\Data;

use Catalog\B2b\Common\Data\Rest\RestResult;
use JMS\Serializer\Annotation as Serializer;

class LanguagesRestResult extends RestResult
{
    /**
     * @var string
     * @Serializer\Type("array<Catalog\B2b\Common\Data\Catalog\Language>")
     */
    public $data = [];
}