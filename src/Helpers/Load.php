<?php

namespace Weiwenhao\Including\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Weiwenhao\Including\Exceptions\IncludeDeniedException;
use Weiwenhao\Including\Exceptions\IteratorBreakException;

trait Load
{
    public function load($constraint, $relationName = null, $parentResource = null)
    {
        $this->parentResource = $parentResource;
        // TODO before callback

        // load relation
        if ($parentResource) {
            $collection = $parentResource->getCollection();

            // post collection load comment. so use comment params
            $collection->loadMissing([$relationName => function ($builder) use ($constraint) {
                // TODO builder callback pass params
                $builder->addSelect(array_keys($constraint['columns']));

//                method_exists($this, 'builder') && $this->builder($builder, $constraint['params']);
            }]);

            $this->setCollection(Collection::make($collection->pluck($relationName)->flatten()));
        }

        // load each
        $this->loadEach($constraint['each']);

        // load meta
        $this->loadMeta($constraint['meta']);


        // into next
        foreach ($constraint['relations'] as $name => $constraint) {
            $resource = $constraint['resource'];
            $resource->load($constraint, $name, $this);
        }

        // TODO after callback
    }

    /**
     * each is free
     * @param $each
     */
    protected function loadEach($each)
    {
        foreach ($each as $name => $constraint) {
            if (!method_exists($this, camel_case($name))) {
                continue;
            }

            foreach ($this->getCollection() as $item) {
                try {
                    // callback
                    $item->{$name} = $this->{camel_case($name)}($item, $constraint['params'] ?? null);
                } catch (IncludeDeniedException $e) {
                    break;
                } catch (IteratorBreakException $e) {
                    $item->{$name} = $e->getData();
                    break;
                }
            }
        }
    }

    protected function loadMeta($meta)
    {
        foreach ($meta as $name => $constraint) {
            try {
                $this->getRootResource()->responseMeta[$name] = $this->{camel_case($name)}($constraint['params'] ?? null);
            } catch (IncludeDeniedException $e) {
                continue;
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
