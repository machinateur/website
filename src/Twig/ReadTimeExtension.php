<?php

declare(strict_types=1);

/*
 * MIT License
 *
 * Copyright (c) 2021-2024 machinateur
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace App\Twig;

use Exception;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class ReadTimeExtension
 * @package App\Twig
 */
class ReadTimeExtension extends ExtensionAbstract
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('read_time', [$this, 'read_time']),
        ];
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('read_time', [$this, 'read_time']),
        ];
    }

    /**
     * @param string $content
     * @param int $words_per_minute
     * @return int
     * @throws Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function read_time($content, $words_per_minute = 200): int
    {
        if (!is_string($content)) {
            $this->throwTypeError('The "read_time" filter/function expects a string as content value, got "%s".', $content);
        }

        if (is_numeric($words_per_minute)) {
            $words_per_minute = abs((int)$words_per_minute);
        } else {
            $this->throwTypeError('The "read_time" filter/function expects a numeric value for words_per_minute, got "%s".', $words_per_minute);
        }

        $words_per_minute = max(180, $words_per_minute);

        return (int)\ceil(\str_word_count(\strip_tags($content)) / $words_per_minute);
    }
}
