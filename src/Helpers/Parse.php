<?php

namespace Weiwenhao\Including\Helpers;

trait Parse
{
    public function parseInclude($string, $begin = null)
    {
        static $offset = 0;
        $array = [];
        $temp = [];
        $string .= ',';

        while (isset($string[$offset]) && $char = $string[$offset++]) {
            if (in_array($char, ['.', ',', '(', ')', '{', '}'], true)) {
                $temp && $temp = $this->toString($temp);

                if (in_array($char, ['.', '{'])) {
                    $array[$temp] = array_merge(
                        $array[$temp] ?? [],
                        $this->parseInclude($string, $char)
                    );
                } elseif ($char === '(') {

                    $array[$temp]['params'] = $this->parseInclude($string, $char);

                } elseif ($char === '}' || ($char === ',' && $begin === '.')) {
                    $array[] = $temp;
                    return $array;

                } elseif ($char === ')') {
                    $array[] = $temp;
                    $array = $this->handleParams($array);
                    return $array;
                } elseif ($char === ',') {
                    $array[] = $temp;
                }

                $temp = [];
            } else {
                $temp[] = $char;
            }
        }

        return $array;
    }

    public function handleParams(array $array): array
    {
        $temp = [];
        foreach ($array as $item) {
            $item = explode(':', $item);
            $temp[$item[0]] = $item[1];
        }

        return $temp;
    }

    public function toString(array $array): string
    {
        return implode('', $array);
    }

    /**
     * 'article.user,comments(limit:3,offset:2){liked,name,user.followed},product'
     *
     * ↓ ↓
     * [
     *    'article' => [
     *          'user'
     *     ],
     *    'comments' => [
     *          'params' => [
     *              'limit' => 3
     *          ],
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
    public function parse($string, $startToken = null, $offset = 0)
    {
        $temp = [];
        $array = [];

        while (isset($string[$offset]) && $char = $string[$offset++]) {
            // 分词
            if ($startToken === '(' && in_array($char, [',', ')'])) {
                $temp && $item = explode(':', implode('', $temp));
                $array[$item[0]] = $item[1];
                $temp = 0;
            } elseif (in_array($char, [',', '}'], true)) {
                $temp && $array[] = implode('', $temp); // 将数组拼接成字符串
                $temp = [];
            } else {
                !in_array($char, ['.', '{']) && $temp[] = $char;
            }

            // 解析
            if ($char === '(') {
                $array['params'] = $this->parseInclude($string, $char, $offset);
            } if (in_array($char, ['.', '{'], true)) { // 入栈
                $array[implode('', $temp)] = $this->parseInclude($string, $char, $offset);
                $temp = [];
            } elseif ($char === '}' || $char === ')' || ($char === ',' && $startToken === '.')) { // 出栈
                return $array;
            }
        }

        $temp && $array[] = implode('', $temp);

        return $array;
    }
}
