<?php
/*
 * MIT License
 *
 * Copyright (c) 2021-2022 machinateur
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

/**
 * Class MiscExtension
 * @package App\Twig
 */
class MiscExtension extends ExtensionAbstract
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('unique', [$this, 'unique']),
        ];
    }

    /**
     * @param array $value
     * @param string $sort_name
     * @return array
     * @throws Exception
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function unique($value, $sort_name = 'regular')
    {
        if (!is_array($value)) {
            $this->throwTypeError('The "unique" filter only works with arrays or "Traversable", got "%s" as first argument.', $value);
        }

        if (!is_string($sort_name)) {
            $this->throwTypeError('The "unique" filter expects a string as sort_name value, got "%s".', $sort_name);
        } elseif (!in_array($sort_name, $whitelist = ['regular', 'numeric', 'string', 'locale_string'], true)) {
            $this->throwTypeError('The "unique" filter allows one of [' . implode(', ', $whitelist)
                . '] as sort_name value, got %s "' . $sort_name . '".', $sort_name);
        }

        /** @var int $sort_flag */
        $sort_flag = constant('SORT_' . strtoupper($sort_name));

        return array_unique($value, $sort_flag);
    }
}
