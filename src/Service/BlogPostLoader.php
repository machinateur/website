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

namespace Machinateur\Website\Service;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Service class for interacting with blog posts from the filesystem.
 */
class BlogPostLoader
{
    public function __construct(
        private readonly CacheItemPoolInterface $contentCache,
        private readonly string                 $viewBasePath,
        private readonly bool                   $debug,
    ) {}

    /**
     * Load the list of blog posts as {@see SplFileInfo} instances.
     *
     * It first checks the content cache.
     *
     * @return array<SplFileInfo>
     */
    public function load(): array
    {
        $blogPosts = null;

        /** @var array<string> $blogPostsPathnames */
        $blogPostsPathnames = $this->contentCache->get('app.blog_posts',
            function (CacheItemInterface $item) use (&$blogPosts): array {
                // Make it expire after one day.
                $item->expiresAfter(
                    new \DateInterval('P1D'),
                );

                // Make sure to set the $blogPosts variable from outer scope.
                $blogPosts = $this->getBlogPosts();

                // Map to relative pathnames.
                return \array_map(
                    function (SplFileInfo $fileInfo): string {
                        return $fileInfo->getRelativePathname();
                    }, $blogPosts,
                );
            },
        );

        // If the value was present in the cache, it would still be null, because the callback was not executed.
        if (null === $blogPosts) {
            if ($this->debug) {
                // Re-scan the in debug mode.
                $blogPosts = $this->getBlogPosts();
            } else {
                $viewBasePath = $this->viewBasePath;

                // Re-construct the `SplFileInfo` array from the relative pathnames.
                $blogPosts = \array_map(
                    static function (string $relativePathname) use ($viewBasePath): SplFileInfo {
                        return new SplFileInfo($viewBasePath . '/' . $relativePathname,
                            \dirname($relativePathname), $relativePathname);
                    }, $blogPostsPathnames,
                );
            }
        }

        return $blogPosts;
    }

    /**
     * @return array<SplFileInfo>
     */
    private function getBlogPosts(): array
    {
        return \iterator_to_array(
            Finder::create()
                ->files()
                ->name(/** @lang PhpRegExp */ '/^[a-z0-9]+(?:-[a-z0-9]+)*.html.twig$/')
                ->path('content/blog/')
                ->in(
                    $this->viewBasePath,
                ),
            false,
        );
    }
}