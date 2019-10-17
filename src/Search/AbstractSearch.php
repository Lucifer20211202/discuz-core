<?php
declare(strict_types=1);

/**
 *      Discuz & Tencent Cloud
 *      This is NOT a freeware, use is subject to license terms
 *
 *      Id: AbstractSearch.php 28830 2019-10-16 11:23 chenkeke $
 */

namespace Discuz\Search;

use Discuz\Contracts\Search\Search;
use Tobscure\JsonApi\Exception\InvalidParameterException;
use Tobscure\JsonApi\Parameters;

abstract class AbstractSearch implements Search
{
    protected $actor;

    protected $query;

    protected $parameter;

    protected $filter = [];

    protected $includes = [];

    private $finalIncludes = [];

    protected $offset = 0;

    private $finalOffset = 0;

    protected $defaultLimit = 10;

    protected $maxLimit = 50;

    private $finalLimit = 0;

    protected $sort = [];

    protected $defaultSort = [];

    private $finalSort = [];

    protected $fields = [];

    protected $defaultFields = [];

    private $finalFields = [];

    public function __construct($actor, $inputs, $query)
    {
        $this->parameter = new Parameters($inputs);

        $this->actor = $actor;

        $this->query = $query;
    }

    /**
     *
     * @return model
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     *
     * @return model
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     *
     * @return array
     * @throws InvalidParameterException
     */
    public function getIncludes()
    {
        if (empty($this->finalIncludes)){
            $this->finalIncludes = $this->parameter->getInclude($this->includes)?:[];
        }
        return $this->finalIncludes;
    }

    /**
     *
     * @return array
     */
    public function getFields()
    {
        if (empty($this->finalFields)){
            $this->finalFields = $this->parameter->getFields()?:$this->defaultFields;
        }
        return $this->finalFields;
    }

    /**
     *
     * @return mixed
     */
    public function getFilter()
    {
        if (empty($this->filter)){
            $this->filter = $this->parameter->getFilter()?:[];
        }
        return $this->filter;
    }

    /**
     *
     * @param int|null $perPage
     * @return int
     * @throws InvalidParameterException
     */
    public function getOffset($perPage = null)
    {
        if (empty($this->finalOffset)){
            $this->finalOffset = $this->parameter->getOffset($this->offset);
        }
        return $this->finalOffset;
    }

    /**
     *
     * @return int
     */
    public function getLimit()
    {
        if (empty($this->finalLimit)){
            $this->finalLimit = $this->parameter->getLimit($this->maxLimit)?:$this->defaultLimit;
        }
        return $this->finalLimit;
    }

    /**
     *
     * @throws InvalidParameterException
     */
    public function getSort()
    {
        if (empty($this->finalSort)){
            $this->finalSort = $this->parameter->getSort($this->sort)?:$this->defaultSort;
        }
        return $this->finalSort;
    }

}