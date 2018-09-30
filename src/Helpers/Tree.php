<?php

namespace Weiwenhao\Including\Helpers;

use Weiwenhao\Including\Exceptions\IncludeDeniedException;

trait Tree
{
    protected function build(array $include)
    {
        $tree = [
            'resource' => $this,
            'columns' => $this->baseColumns,
            'meta' => [],
            'each' => [],
            'relations' => []
        ];

        $this->includeRelations = $this->structureIncludeRelations($this->includeRelations);

        foreach ($include as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
            }

            try {
                if (in_array($name, $this->includeColumns, true)) {
                    // callback
                    method_exists($this, camel_case($name)) && $this->{camel_case($name)}();

                    $tree['columns'][] = $name;
                } elseif (isset($this->includeRelations[$name])) {
                    // callback
                    method_exists($this, camel_case($name)) && $this->{camel_case($name)}();

                    $class = $this->includeRelations[$name]['resource'];
                    $resource = new $class();
                    $tree['relations'][$name] = $resource->build(is_array($constraint) ? $constraint : []);
                } elseif (in_array($name, $this->includeMeta, true)) {
                    $tree['meta'][] = $name;
                } elseif (in_array($name, $this->includeEach, true)) {
                    $tree['each'][] = $name;
                }
            } catch (IncludeDeniedException $e) {
                continue;
            }
        }

        return $tree;
    }


    /**
     * ['user', 'posts']
     *
     * [
     *      'user' => [
     *          'resource' => xxx
     *      ],
     *      'posts' => [
     *          'resource' => xxx
     *      ]
     * ]
     */
    protected function structureIncludeRelations($relations)
    {
        $temp = [];
        foreach ($relations as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
                $constraint = [];
            }

            if (!isset($constraint['resource'])) {
                $constraint['resource'] = config('including.resource_namespace', "App\\Resources\\")
                    . studly_case(str_singular($name).'_resource');
            }

            $temp[$name] = $constraint;
        }

        return $temp;
    }
}
