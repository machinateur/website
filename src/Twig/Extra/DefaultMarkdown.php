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

namespace Machinateur\Website\Twig\Extra;

use ParsedownExtra;
use Twig\Extra\Markdown\DefaultMarkdown as DefaultMarkdownBase;
use Twig\Extra\Markdown\ErusevMarkdown;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * Add support for {@see ParsedownExtra} to the original {@see DefaultMarkdownBase} implementation.
 *
 * Decoration is applied by setting the original definition's class to this one in `config/services.yaml`.
 */
class DefaultMarkdown extends DefaultMarkdownBase
{
    protected ?MarkdownInterface $converter = null;

    public function __construct()
    {
        if (\class_exists(ParsedownExtra::class)) {
            $this->converter = new ErusevMarkdown(
                new ParsedownExtra(),
            );

            return;
        }

        parent::__construct();
    }

    public function convert(string $body): string
    {
        if ($this->converter) {
            return $this->converter->convert($body);
        }

        return parent::convert($body);
    }
}