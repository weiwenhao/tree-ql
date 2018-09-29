<?php

namespace Weiwenhao\Including\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class IncludesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Builder::macro('includeSelect', function ($query) {

        });
    }

    public function register()
    {

    }
}
