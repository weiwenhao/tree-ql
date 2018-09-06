<?php

namespace Weiwenhao\Including;

use Illuminate\Database\Eloquent\Builder;
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

    private $model;

    private $builder;

    private $query;

    private $dataTree;

    private $root;

    private $dataFactory;

    private $include;

    private $includeTree;

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
    public function parse($builder)
    {
        $this->builder = $builder;

        $requestInclude = $this->parseInclude(request('include'));

        $this->includeTree = $this->structureTree($requestInclude);

        return $this;
    }

    public function structureTree(array $include)
    {
          $tree = [
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
                $constraint['resource'] = "Weiwenhao\\Including\\Tests\\Stubs\\" . studly_case(str_singular($name).'_resource');
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
        if (is_null($columns)) {
            $columns = $this->baseColumns;
        }

        $columns = array_merge($columns, $this->query->getIncludeColumns());

        $item = parent::findOrFail($id, $columns);

        $this->includeRelations($item);

        return $item;
    }

    /**
     * @param $relations
     * @param $collection
     */
    public function includeRelations($relations, $collection)
    {

        // array_keys
        foreach ($relations as $key => $relation) {
            $resource = new $relation->resource;

            $resource->includeRelations($relations['key'], $collection); // this is tree
            // children
        }
    }
}
