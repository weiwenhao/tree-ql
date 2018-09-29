<?php

namespace Weiwenhao\Including\Helpers;

trait Format
{
    public function toArray()
    {
        // 解析结构树进行toArray
        return [
            'data' => $this->getData()->toArray(),
            'meta' => $this->meta,
        ];
    }
}
