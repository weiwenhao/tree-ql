<?php

namespace Weiwenhao\TreeQL\Helpers;

trait Parse
{
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
     *              'limit' => 3,
     *              'offset' => 2
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
    public function parseInclude($string, $begin = null)
    {
        static $offset = 0;
        $array = [];
        $temp = [];
        !$begin && $string .= ",";

        while (isset($string[$offset])) {
            $char = $string[$offset++];

            if (in_array($char, ['.', ',', '(', ')', '{', '}', ';'], true)) {
                is_array($temp) && $temp = $this->toString($temp);

                if (in_array($char, ['.', '{'])) {
                    $array[$temp] = array_merge(
                        $array[$temp] ?? [],
                        $this->parseInclude($string, $char)
                    );
                } elseif ($char === '(') {

                    $array[$temp]['params'] = $this->parseInclude($string, $char);
                    // 接下来需要继续用到temp,因此不走到下面的清空temp部分
                    if (in_array($string[$offset], ['.', '{'])) {
                        continue;
                    }

                } elseif (($char === '}' || $char === ',') && $begin === '.') {
                    $temp && $array[] = $temp;
                    // offset 使用了预偏移的模式, 所以这里要 - 2
                    $offset = $offset - 1;

                    return $array;
                } elseif ($char === '}') {
                    $temp && $array[] = $temp;
                    return $array;

                } elseif ($char === ')') {
                    $temp && $array[] = $temp;
                    $array = $this->handleParams($array);
                    return $array;
                } elseif ($char === ',') {
                    $temp && $array[] = $temp;
                }

                $temp = [];
            } else {
                $temp[] = $char;
            }
        }

        // 结束清空$offset
        $offset = 0;
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
}
