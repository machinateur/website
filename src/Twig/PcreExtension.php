<?php
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

namespace Machinateur\Website\Twig;

use Exception;
use Twig\Error\RuntimeError;
use Twig\TwigFilter;

/**
 * Class PcreExtension
 *
 * @package Machinateur\Website\Twig
 */
class PcreExtension extends AbstractExtension
{
    /**
     * PcreExtension constructor.
     * @throws Exception
     */
    public function __construct()
    {
        if (!\extension_loaded('pcre')) {
            throw new Exception("The Twig PCRE extension requires the PHP PCRE extension.");
        }
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('pcre_replace', [$this, 'pcre_replace']),
        ];
    }

    /**
     * @param string|array|string[] $value
     * @param string|array|string[] $pattern
     * @param string|array|string[] $replacement
     * @param int $limit
     * @return string|array|string[]|null
     * @throws Exception
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function pcre_replace($value, $pattern, $replacement = '', $limit = -1)
    {
        if (!isset($value)) {
            return null;
        } elseif (!(\is_array($value) || \is_string($value))) {
            $this->throwTypeError('The "pcre_replace" filter only works with arrays or "Traversable", got "%s" as first argument.', $value);
        }

        if (\is_array($pattern)) {
            \array_walk($pattern, function ($value, $key): void {
                $this->assertPatternWithoutModifier((string)$value);
            });
        } elseif (\is_string($pattern)) {
            $this->assertPatternWithoutModifier($pattern);
        } else {
            $this->throwTypeError('The "pcre_replace" filter expects a string, an array or "Traversable" as pattern values, got "%s".', $pattern);
        }

        if(!(\is_array($replacement) || \is_string($replacement))) {
            $this->throwTypeError('The "pcre_replace" filter expects a string, an array or "Traversable" as replacement values, got "%s".', $replacement);
        }

        if (\is_numeric($limit)) {
            $limit = (int)$limit;
        } else {
            $this->throwTypeError('The "pcre_replace" filter expects a number as limit value, got "%s".', $limit);
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        return \preg_replace($pattern, $replacement, $subject = $value, $limit, $count);
    }

    /**
     * @param string $pattern
     * @param string $modifier
     * @throws RuntimeError
     */
    protected function assertPatternWithoutModifier(string $pattern, string $modifier = 'e'): void
    {
        $position     = \strrpos($pattern, $pattern[0]);
        $modifierPart = \substr($pattern, $position + 1);

        if (\strpos($modifierPart, $modifier) !== false) {
            throw new RuntimeError(\sprintf('Using the "%s" modifier for regular expressions is not allowed.', $modifier));
        }
    }
}
