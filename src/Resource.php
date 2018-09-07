<?php

namespace Weiwenhao\Including;

use App\Resources\ProductVariantResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

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

    private $tree;

    private $collection;

    private $meta = [];

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
    public static function parse($builder)
    {
        $resource = new static();

        $resource->builder = $builder;

        $requestInclude = $resource->parseInclude(request('include'));

        $resource->tree = $resource->structureTree($requestInclude);

        return $resource;
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
    private function parseInclude($string, $startToken = null)
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
                $array[implode('', $temp)] = $this->parseInclude($string, $char);
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
        // 加载父级需要的在下的关联
        if ($parentResource) {
            $collection = $parentResource->getCollection();
            $collection->loadMissing([$relationName => function ($builder) use ($constraint) {
                $builder->addSelect($constraint['columns']);
            }]);

            $this->setCollection(Collection::make($collection->pluck($relationName)->flatten()));
        }

        // 交出控制权
        foreach ($constraint['relations'] as $name => $constraint) {
            $childResource = $constraint['resource'];
            $childResource->load($constraint, $name, $this);
        }
    }
}
