<?php

namespace Weiwenhao\TreeQL\Helpers;

use Illuminate\Database\Eloquent\Collection;

trait Format
{
    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->dataToArray($this->getResponseData(), $this->getTree()),
            'meta' => $this->getResponseMeta(),
        ];
    }


    private function dataToArray($data, $tree)
    {
        $temp = [];
        if ($data instanceof Collection) {
            foreach ($data as $model) {
                $temp[] = $this->dataToArray($model, $tree);
            }
        } else {
            $attributes = array_merge($tree['columns'], $tree['each']);

            foreach ($attributes as $name => $constraint) {
                $temp[$name] = $data->{$name};
            }

            foreach ($tree['relations'] as $attribute => $tree) {
                $temp[$attribute] = $this->dataToArray($data[$attribute], $tree);
            }
        }

        return $temp;
    }
}
