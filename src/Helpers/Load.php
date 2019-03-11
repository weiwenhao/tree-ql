<?php

namespace Weiwenhao\TreeQL\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Weiwenhao\TreeQL\Exceptions\IncludeDeniedException;
use Weiwenhao\TreeQL\Exceptions\IteratorBreakException;

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

                $builder->addSelect(array_keys($constraint['columns']));

                method_exists($this, 'loadConstraint') && $this->loadConstraint($builder, $constraint['params'] ?? []);
            }]);

            $this->setCollection(Collection::make($collection->pluck($relationName)->flatten()));
        }

        // load custom
        $this->loadCustom($constraint['custom']);

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
     * custom is free
     * @param $custom
     */
    protected function loadCustom($custom)
    {
        foreach ($custom as $name => $constraint) {
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
