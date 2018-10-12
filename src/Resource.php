<?php

namespace Weiwenhao\Including;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Weiwenhao\Including\Helpers\Format;
use Weiwenhao\Including\Helpers\Load;
use Weiwenhao\Including\Helpers\Parse;
use Weiwenhao\Including\Helpers\Tree;

abstract class Resource implements Arrayable
{
    use Parse, Tree, Format, Load;



    protected $baseColumns = [];
    protected $includeColumns = [];
    protected $includeRelations = [];
    protected $includeMeta = [];
    protected $includeEach = [];

    private $meta = [];
    private $data;
    private $tree;

    private $collection;

    private $parentResource;

    private $parsedInclude;


    /**
     * limit 使用get
     * per_page 使用paginate
     * 默认为 all
     * allow all
     *
     * UserResource::parse();
     * UserResource::parse()->get();
     * @param $data
     * @param null $include
     * @return Resource
     */
    public static function make($data, $include = null)
    {
        $resource = new static();

        // 分情况处理
        if ($data instanceof Model) {
            $resource->setCollection(Collection::make([$data]));
        } elseif ($data instanceof Collection) {
            $resource->setCollection($data);
        } elseif ($data instanceof LengthAwarePaginator) {
            $resource->meta['pagination'] = $resource->parsePagination($data);
            $data = $data->getCollection();

            $resource->setCollection($data);
        }

        $resource->data = $data;

        $parsedInclude = $resource->parseInclude($include ?? request('include'));
        $resource->tree = $resource->build($parsedInclude);
        $resource->load($resource->tree);
        return $resource;
    }

    private function parsePagination(LengthAwarePaginator $paginate)
    {
        return [
            'per_page' => $paginate->perPage(),
            'total' => $paginate->total(),
            'current' => $paginate->currentPage(),
            'next' => $paginate->nextPageUrl(),
            'previous' => $paginate->previousPageUrl(),
            'last' => $paginate->lastPage(),
        ];
    }

    /**
     * @return array
     */
    public function getParsedInclude(): array
    {
        return $this->parsedInclude;
    }

    /**
     * @param mixed $parsedInclude
     */
    public function setParsedInclude($parsedInclude): void
    {
        $this->parsedInclude = $parsedInclude;
    }

    /**
     * @return array
     */
    public function getIncludeColumns(): array
    {
        return $this->includeColumns;
    }

    /**
     * @param array $includeColumns
     */
    public function setIncludeColumns(array $includeColumns): void
    {
        $this->includeColumns = $includeColumns;
    }

    /**
     * @return array
     */
    public function getIncludeRelations(): array
    {
        return $this->includeRelations;
    }

    /**
     * @param array $includeRelations
     */
    public function setIncludeRelations(array $includeRelations): void
    {
        $this->includeRelations = $includeRelations;
    }

    /**
     * @return array
     */
    public function getIncludeMeta(): array
    {
        return $this->includeMeta;
    }

    /**
     * @param array $includeMeta
     */
    public function setIncludeMeta(array $includeMeta): void
    {
        $this->includeMeta = $includeMeta;
    }

    /**
     * @return array
     */
    public function getIncludeEach(): array
    {
        return $this->includeEach;
    }

    /**
     * @param array $includeEach
     */
    public function setIncludeEach(array $includeEach): void
    {
        $this->includeEach = $includeEach;
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param mixed $collection
     */
    public function setCollection($collection): void
    {
        $this->collection = $collection;
    }

    /**
     * @return mixed
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @param mixed $tree
     */
    public function setTree($tree): void
    {
        $this->tree = $tree;
    }

    /**
     * @return mixed
     */
    public function getParentResource()
    {
        return $this->parentResource;
    }

    /**
     * @param mixed $parentResource
     */
    public function setParentResource($parentResource): void
    {
        $this->parentResource = $parentResource;
    }

    /**
     * @return array
     */
    public function getBaseColumns(): array
    {
        return $this->baseColumns;
    }

    /**
     * @param array $baseColumns
     */
    public function setBaseColumns(array $baseColumns): void
    {
        $this->baseColumns = $baseColumns;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }
}
