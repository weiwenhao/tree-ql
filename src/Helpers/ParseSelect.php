<?php

namespace Weiwenhao\Including\Helpers;

trait ParseSelect
{
    public function scopeParseSelect($builder, $resource)
    {
        if (is_string($resource)) {
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
