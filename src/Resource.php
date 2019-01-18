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

    protected $default = [];
    protected $columns = [];
    protected $relations = [];
    protected $meta = [];
    protected $each = [];

    private $params;

    private $responseMeta = [];
    private $responseData;

    private $tree;

    private $collection;

    private $parentResource;

    private $parsedInclude;

    private $dataType;


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
            $resource->dataType = 'model';
            $resource->setCollection(Collection::make([$data]));
        } elseif ($data instanceof Collection) {
            $resource->dataType = 'collection';
            $resource->setCollection($data);
        } elseif ($data instanceof LengthAwarePaginator) {
            $resource->dataType = 'pagination';
            $resource->meta['pagination'] = $resource->parsePagination($data);

            $data = $data->getCollection();

            $resource->setCollection($data);
        }

        $resource->setResponseData($data);

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
    public function getDefault(): array
    {
        return $this->default;
    }

    /**
     * @param array $default
     */
    public function setDefault(array $default): void
    {
        $this->default = $default;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        $temp = [];
        foreach ($this->columns as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            $temp[$key] = $value;
        }

        return $temp;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        $temp = [];
        foreach ($this->relations as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
                $constraint = [];
            }

            if (!isset($constraint['resource'])) {
                $constraint['resource'] = config('including.resource_namespace', "App\\Resources\\")
                    . studly_case(str_singular($name) . '_resource');
            }

            $temp[$name] = $constraint;
        }

        return $temp;
    }

    /**
     * @param array $relations
     */
    public function setRelations(array $relations): void
    {
        $this->relations = $relations;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        $temp = [];
        foreach ($this->meta as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            $temp[$key] = $value;
        }

        return $temp;
    }

    /**
     * @param array $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * @return array
     */
    public function getEach(): array
    {
        $temp = [];
        foreach ($this->each as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            $temp[$key] = $value;
        }

        return $temp;
    }

    /**
     * @param array $each
     */
    public function setEach(array $each): void
    {
        $this->each = $each;
    }




    /**
     * @return array
     */
    public function getResponseMeta(): array
    {
        return $this->responseMeta;
    }

    /**
     * @param array $responseMeta
     */
    public function setResponseMeta(array $responseMeta): void
    {
        $this->responseMeta = $responseMeta;
    }

    /**
     * @return mixed
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * @param mixed $responseData
     */
    public function setResponseData($responseData): void
    {
        $this->responseData = $responseData;
    }
}
