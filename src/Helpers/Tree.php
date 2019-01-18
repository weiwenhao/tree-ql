<?php

namespace Weiwenhao\TreeQL\Helpers;

use Weiwenhao\TreeQL\Exceptions\IncludeDeniedException;

trait Tree
{
    /**
     * 语义化
     * @param array $include
     * @return array
     */
    protected function build(array $include)
    {
        $columns = $this->getColumns();
        $relations = $this->getRelations();
        $meta = $this->getMeta();
        $each = $this->getEach();

        $tree = [
            'resource' => $this,
            'columns' => $this->parseDefault($columns),
            'meta' => $this->parseDefault($meta),
            'each' => $this->parseDefault($each),
            'relations' => $this->parseDefault($relations)
        ];


        foreach ($include as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
                $constraint = [];
            }

            if (isset($relations[$name])) {
                $temp = $relations[$name];

                if (isset($constraint['params'])) {
                    $temp['params'] = $constraint['params'];
                }

                $class = $relations[$name]['resource'];
                $resource = new $class();

                $temp = array_merge($temp, $resource->build(is_array($constraint) ? $constraint : []));

                $tree['relations'][$name] = $temp;

            } else {
                isset($columns[$name]) && $key = 'columns';
                isset($meta[$name]) && $key = 'meta';
                isset($each[$name]) && $key = 'each';

                if (!isset($key)) {
                    continue;
                }

                $temp = ${$key}[$name];

                if (isset($constraint['params'])) {
                    $temp['params'] = $constraint['params'];
                }

                $tree[$key][$name] = $temp;
            }
        }

        return $tree;
    }

    public function parseDefault($data)
    {
        $default = $this->getDefault();
        $temp = [];

        foreach ($data as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
                $constraint = [];
            }

            if (in_array($name, $default)) {
                $temp[$name] = $constraint;
            }
        }

        return $temp;
    }

}
