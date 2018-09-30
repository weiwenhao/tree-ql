<?php

namespace Weiwenhao\Including\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class IncludingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Builder::macro('includeSelect', function ($resource = null) {
            if ($resource) {
                if (is_string($resource)) {
                    $resource = new $resource;
                }
            } else {
                $resource = config('including.resource_namespace') .
                    studly_case(str_singular($this->getModel()->getTable()) .'_resource');
                $resource = new $resource;
            }


            $include = $resource->parseInclude(request('inlcude'));
            $columns = $resource->getBaseColumns();
            $includeColumns = $resource->getIncludeColumns();

            foreach ($include as $name => $constraint) {
                if (is_numeric($name)) {
                    $name = $constraint;
                }

                if (in_array($name, $includeColumns, true)) {
                    $columns[] = $name;
                }
            }

            return $this->addSelect($columns);
        });
    }

    public function register()
    {

    }
}
