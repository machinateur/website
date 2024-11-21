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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * A command to generate the sitemap in text format.
 */
#[AsCommand('sitemap')]
class SitemapCommand extends Command
{
    use UrlInputTrait {
        UrlInputTrait::configure as protected configureUrlInput;
    }

    public const DEFAULT_SCHEME = 'https';
    public const DEFAULT_HOST   = 'machinateur.local';
    public const DEFAULT_PORT   = '8000';

    private string $contentPath;

    /**
     * Called from the container, see `services.yaml`.
     */
    public function getContentPath(): string
    {
        return $this->contentPath;
    }

    public function setContentPath(string $contentPath): void
    {
        $this->contentPath = $contentPath;
    }

    protected function configure(): void
    {
        $defaultSitemapPath = \dirname(__DIR__, 2) . '/public/sitemap.txt';

        $this
            ->setDescription('Create the sitemap (static text format for google).')
            ->addArgument('sitemap-path', InputArgument::OPTIONAL,
                'The path to write the sitemap to.', $defaultSitemapPath)
            ->addArgument('twig-path', InputArgument::OPTIONAL,
                'The path to scan for content struct.')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A filter regex pattern to match against the sitemap urls.')
        ;

        $this->configureUrlInput();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);

        if (null !== ($twigPath = $input->getArgument('twig-path'))) {
            $this->setContentPath($twigPath);
        }

        $pages = \iterator_to_array(
            Finder::create()
                ->files()
                ->depth('<= 2')
                ->name(/** @lang PhpRegExp */ '/^[a-z0-9]+(?:-[a-z0-9]+)*.html.twig$/')
                ->path('content/')
                ->in(
                    $this->getContentPath(),
                ),
            false,
        );

        $generateUrl = fn (string $urlPath): string => $this->getUrlFromInput($input, $urlPath);

        $sitemapContent = \implode("\n",
                \array_filter(
                    \array_map(
                        static function (SplFileInfo $fileInfo) use ($generateUrl): string {
                            // Build path (transferred from twig logic).
                            $urlPath = \str_replace('\\', '/', \substr($fileInfo->getRelativePathname(),
                                \strlen('content'), -\strlen('.html.twig')));

                            // Return the full URL.
                            return $generateUrl($urlPath);
                        }, $pages,
                    ),
                    static function (string $url) use ($input): bool {
                        $filterArray = $input->getOption('filter');

                        // Apply each filter from the input.
                        foreach ($filterArray as $filter) {
                            if (1 === \preg_match($filter, $url)) {
                                return true;
                            }
                        }

                        return 0 === \count($filterArray);
                    },
                ),
            )
            . "\n";

        $sitemapPath  = $input->getArgument('sitemap-path');
        $bytesWritten = \file_put_contents($sitemapPath, $sitemapContent);

        if (false !== $bytesWritten) {
            $console->success(\sprintf('Sitemap successfully generated at path "%s", %d bytes written.', $sitemapPath, $bytesWritten));
        } else {
            $console->error(\sprintf('Sitemap could not be generated at path "%s"!', $sitemapPath));
        }

        return $bytesWritten
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
