<?php

namespace Weiwenhao\Including\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait Format
{
    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->dataToArray($this->getData(), $this->getTree()),
            'meta' => $this->meta,
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

            foreach ($attributes as $attribute) {
                $temp[$attribute] = $data->{$attribute};
            }

            foreach ($tree['relations'] as $attribute => $tree) {
                $temp[$attribute] = $this->dataToArray($data[$attribute], $tree);
            }
        }

        return $temp;
    }
}
