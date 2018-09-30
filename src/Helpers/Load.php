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

        // load relation
        if ($parentResource) {
            $collection = $parentResource->getCollection();

            $collection->loadMissing([$relationName => function ($builder) use ($constraint) {
                $builder->addSelect($constraint['columns']);
            }]);

            $this->setCollection(Collection::make($collection->pluck($relationName)->flatten()));
        }

        // load each
        $this->loadEach($constraint['each']);

        // load meta
        foreach ($constraint['meta'] as $name) {
            try {
                $this->getRootResource()->meta[$name] = $this->{camel_case($name)}();
            } catch (IncludeDeniedException $e) {
                continue;
            }
        }

        // into next
        foreach ($constraint['relations'] as $name => $constraint) {
            $childResource = $constraint['resource'];
            $childResource->load($constraint, $name, $this);
        }
    }

    /**
     * each is free
     * @param $each
     */
    protected function loadEach($each)
    {
        foreach ($each as $name) {
            if (method_exists($this, camel_case($name))) {
                continue;
            }

            foreach ($this->getCollection() as $item) {
                try {
                    $item->{$name} = $this->{camel_case($name)}($item);
                } catch (IncludeDeniedException $e) {
                    break;
                } catch (IteratorBreakException $e) {
                    $item->{$name} = $e->getData();
                    break;
                }
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
