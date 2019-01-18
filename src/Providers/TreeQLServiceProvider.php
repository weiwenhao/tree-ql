<?php

namespace Weiwenhao\TreeQL\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class TreeQLServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->builderMacro();

        $this->publishes([
            __DIR__.'/../../config/tree-ql.php' => config_path('tree-ql.php'),
        ]);
    }

    public function register()
    {

    }

    private function builderMacro()
    {
        Builder::macro('columns', function ($resource = null) {
            if ($resource) {
                if (is_string($resource)) {
                    $resource = new $resource;
                }
            } else {
                $resource = config('tree-ql.resource_namespace', "App\\Resources\\") .
                    studly_case(str_singular($this->getModel()->getTable()) .'_resource');

                $resource = new $resource;
            }

            $include = $resource->parseInclude(request('include'));

            // $this->parseDefault($columns),
            $availableInclude = $resource->getColumns();
            $columns = $resource->parseDefault($availableInclude);

            foreach ($include as $name => $constraint) {
                if (is_numeric($name)) {
                    $name = $constraint;
                    $constraint = [];
                }

                if (isset($availableInclude[$name])) {
                    $columns[$name] = $constraint;
                }
            }

            return $this->addSelect(array_keys($columns));
        });
    }
}
