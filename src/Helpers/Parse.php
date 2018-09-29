<?php

namespace Weiwenhao\Including\Helpers;

trait Parse
{
    /**
     * 'article.user,comments{liked,name,user.followed},product'
     *
     * ↓ ↓
     * [
     *    'article' => [
     *          'user'
     *     ],
     *    'comments' => [
     *          'liked',
     *          'name',
     *          'user' => [
     *              'followed'
     *          ]
     *     ],
     *     'product'
     * ]
     * @param $string
     * @param null $startToken
     * @param int $offset
     * @return array
     */
    public function parseInclude($string, $startToken = null, $offset = 0)
    {
        $temp = [];
        $array = [];

        while (isset($string[$offset]) && $char = $string[$offset++]) {
            // symbol 分词
            if (in_array($char, [',', '}'], true)) {
                $temp && $array[] = implode('', $temp);
                $temp = [];
            } else {
                !in_array($char, ['.', '{']) && $temp[] = $char;
            }


            // 解析
            if (in_array($char, ['.', '{'], true)) { // 入栈
                $array[implode('', $temp)] = $this->parseInclude($string, $char, $offset);
                $temp = [];
            } elseif ($char === '}' || ($char === ',' && $startToken === '.')) { // 出栈
                return $array;
            }
        }

        $temp && $array[] = implode('', $temp);

        return $array;
    }
}
