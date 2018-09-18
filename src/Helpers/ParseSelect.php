<?php

namespace Weiwenhao\Including\Helpers;

trait ParseSelect
{
    public function scopeParseSelect($builder, $resource = null)
    {
        if ($resource) {
            if (is_string($resource)) {
                $resource = new $resource;
            }
        } else {
            $resource = config('including.resource_namespace') .
                studly_case(str_singular($this->getTable()) .'_resource');

            $resource = new $resource;
        }


        $include = $resource::getParsedInclude();
        $columns = $resource->getBaseColumns();
        $includeColumns = $resource->getIncludeCoums();

        foreach ($include as $name => $constraint) {
            if (is_numeric($name)) {
                $name = $constraint;
            }

            if (in_array($name, $includeColumns, true)) {
                $columns[] = $name;
            }
        }

        return $builder->addSelect($columns);
    }
}
