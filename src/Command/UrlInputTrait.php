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

namespace Machinateur\Website\Command;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait UrlInputTrait
{
    protected function configure(): void
    {
        $this
            ->addOption('url-scheme', null, InputOption::VALUE_REQUIRED,
                'The url scheme to use.', self::DEFAULT_SCHEME)
            ->addOption('url-host', null, InputOption::VALUE_REQUIRED,
                'The url host to use.', self::DEFAULT_HOST)
            ->addOption('url-port', null, InputOption::VALUE_REQUIRED,
                'The url port to use.', self::DEFAULT_PORT)
        ;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getUrlFromInput(InputInterface $input, string $urlPath): string
    {
        $urlScheme = $input->getOption('url-scheme');
        $urlHost   = $input->getOption('url-host');
        $urlPort   = $input->getOption('url-port');
        if ('http' === $urlScheme && 80 != $urlPort) {
            $urlPort = ':' . $urlPort;
        } elseif ('https' === $urlScheme && 443 != $urlPort) {
            $urlPort = ':' . $urlPort;
        } else {
            $urlPort = '';
        }
        if ( ! \str_starts_with($urlPath, '/')) {
            $urlPath = '/' . $urlPath;
        }

        // Return the full URL.
        return "{$urlScheme}://{$urlHost}{$urlPort}{$urlPath}";
    }
}