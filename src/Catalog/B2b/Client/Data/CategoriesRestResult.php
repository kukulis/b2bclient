<?php
/**
 * CategoriesRestResult.php
 * Created by Giedrius Tumelis.
 * Date: 2021-04-13
 * Time: 11:28
 */

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