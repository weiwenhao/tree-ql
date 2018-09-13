<?php

namespace Weiwenhao\Including;

use App\Resources\ProductVariantResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Weiwenhao\Including\Exceptions\IteratorBreakException;
use Weiwenhao\Including\Exceptions\IteratorContinueException;

abstract class Resource
{
    // 数据初始化
    protected $baseColumns = [];

    // 自动处理
    protected $includeColumns = [];

    protected $includeRelations = [];

    // 手动处理
    protected $includeMeta = [];
    protected $includeOther = [];

    private $builder;

    public $tree;

    private $collection;

    private $meta = [];

    public $parentResource = null;

    protected static $parsedInclude;


    public function getIncludeCoums()
    {
        return $this->includeColumns;
    }

    public function getBaseColumns()
    {
        return $this->baseColumns;
    }
    /**
     * limit 使用get
     * per_page 使用paginate
     * 默认为 all
     * allow all
     *
     * UserResource::parse();
     * UserResource::parse()->get();
     * @param null $builder
     * @return $this
     */
    public static function parse($data)
    {
        $resource = new static();

        // 分布处理
        if ($data instanceof Model) {
            $resource->setCollection(Collection::make([$data]));
        } elseif ($data instanceof Collection) {
            $resource->setCollection($data);
        } elseif ($data instanceof LengthAwarePaginator) {
            $resource->meta['pagination'] = $resource->parsePagination($data);
            $data = $data->getCollection();

            $resource->setCollection($data);
        }


        $parsedInclude = static::getParsedInclude();
        $resource->tree = $resource->structureTree($parsedInclude);

        $resource->load($resource->tree);

        return [
            'data' => $data,
            'meta' => $resource->meta,
        ];
        return $data;
    }

    public function structureTree(array $include)
    {
        $tree = [
            'resource' => $this,
            'columns' => $this->baseColumns,
            'meta' => [],
            'other' => [],
            'relations' => []
        ];

        $this->formatIncludeConfig();

        foreach ($include as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
            }

            if (in_array($name, $this->includeColumns, true)) {
                $tree['columns'][] = $name;
            } elseif (in_array($name, $this->includeMeta, true)) {
                $tree['meta'][] = $name;
            } elseif (in_array($name, $this->includeOther, true)) {
                $tree['other'][] = $name;
            } elseif (isset($this->includeRelations[$name])) {
                $class = $this->includeRelations[$name]['resource'];
                $resource = new $class();
                $tree['relations'][$name] = $resource->structureTree(is_array($constraint) ? $constraint : []);
            }
        }

            return $tree;
    }

    public function formatIncludeConfig()
    {
        $temp = [];
        foreach ($this->includeRelations as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
                $constraint = [];
            }

            if (!isset($constraint['resource'])) {
                $constraint['resource'] = "Weiwenhao\\Including\\Tests\\Stubs\\"
                    . studly_case(str_singular($name).'_resource');
            }

            $temp[$name] = $constraint;
        }
        $this->includeRelations = $temp;
    }

    public static function getParsedInclude()
    {
        if (!static::$parsedInclude) {
            static::$parsedInclude = static::parseInclude(request('include'));
        }

        return static::$parsedInclude;
    }


    /**
     * 'article.user,comments{liked,name,user.followed},product'
     *
     * ↓ ↓
     * [
     *    'article' => [
     *          'user'
     *     ],
     *    'comments' => [
     *          'liked',
     *          'name',
     *          'user' => [
     *              'followed'
     *          ]
     *     ],
     *     'product'
     * ]
     * @param $string
     * @param null $startToken
     * @return array
     */
    private static function parseInclude($string, $startToken = null)
    {
        static $offset = 0;
        $temp = [];
        $array = [];

        while (isset($string[$offset]) && $char = $string[$offset++]) {
            // symbol 分词
            if (in_array($char, [',', '}'], true)) {
                $temp && $array[] = implode('', $temp);
                $temp = [];
            } else {
                !in_array($char, ['.', '{']) && $temp[] = $char;
            }


            // 解析
            if (in_array($char, ['.', '{'], true)) { // 入栈
                $array[implode('', $temp)] = static::parseInclude($string, $char);
                $temp = [];
            } elseif ($char === '}' || ($char === ',' && $startToken === '.')) { // 出栈
                return $array;
            }
        }

        $temp && $array[] = implode('', $temp);
        return $array;
    }

    public function findOrFail($id, $columns = null)
    {
        $columns = array_merge($this->tree['columns'], $columns ?? []);

        $result = $this->builder->findOrFail($id, $columns);
        $this->collection = new Collection([$result]);
        $this->load($this->tree);

        return [
            'data' => $result,
            'meta' => $this->meta,
        ];
    }

    public function parsePagination(LengthAwarePaginator $paginate)
    {
        return [
            'per_page' => $paginate->perPage(),
            'total' => $paginate->total(),
            'current' => $paginate->currentPage(),
            'next' => $paginate->nextPageUrl(),
            'previous' => $paginate->previousPageUrl()
        ];
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection)
    {
        return $this->collection = $collection;
    }

    public function load($constraint, $relationName = null, $parentResource = null)
    {
        // 记录parent
        $this->parentResource = $parentResource;

        // 加载父级需要的在下的关联
        if ($parentResource) {
            $collection = $parentResource->getCollection();
            $collection->loadMissing([$relationName => function ($builder) use ($constraint) {
                $builder->addSelect($constraint['columns']);
            }]);

            $this->setCollection(Collection::make($collection->pluck($relationName)->flatten()));

            // other and callback
            $this->loadOther($constraint['other']);
        }


        // 加载meta
        foreach ($constraint['meta'] as $name) {
            $this->getRootResource()->meta[$name] = $this->{camel_case($name)}();
        }

        // 交出控制权
        foreach ($constraint['relations'] as $name => $constraint) {
            $childResource = $constraint['resource'];
            $childResource->load($constraint, $name, $this);
        }
    }

    protected function loadOther($other)
    {
        foreach ($other as $name) {
            foreach ($this->getCollection() as $item) {
                try {
                    $item->{$name} = $this->{camel_case($name)}($item);
                } catch (IteratorContinueException $e) {
                    continue;
                } catch (IteratorBreakException $e) {
                    break;
                }
            }
        }
    }

    public function getRootResource()
    {
        $resource = $this;

        while (true) {
            if (!$resource->parentResource) {
                return $resource;
            }

            $resource = $resource->parentResource;
        }
    }
}
