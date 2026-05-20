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

use Machinateur\Website\Controller\FeedController;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand('feed')]
class FeedCommand extends Command
{
    use UrlInputTrait {
        UrlInputTrait::configure as protected configureUrlInput;
    }

    public const DEFAULT_SCHEME = 'https';
    public const DEFAULT_HOST   = 'machinateur.local';
    public const DEFAULT_PORT   = '8000';

    public function __construct(
        private readonly RouterInterface $router,
        private readonly FeedController  $feed,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $defaultFeedPath = \dirname(__DIR__, 2) . '/public/feed.xml';

        $this
            ->setDescription('Create the RSS feed (static XML format).')
            ->addArgument('path', InputArgument::OPTIONAL,
                'The path to write the RSS feed to.', $defaultFeedPath)
        ;

        $this->configureUrlInput();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);

        $uri     = $this->getUrlFromInput($input, '/feed');
        $request = Request::create($uri);

        $this->router->getContext()
            ->fromRequest($request);

        $response = $this->feed->view();

        $statusCode    = $response->getStatusCode();
        $statusText    = Response::$statusTexts[$statusCode] ?? 'Unknown status code';
        $statusMessage = \sprintf('Response status code %d: %s.', $statusCode, $statusText);

        if (200 === $statusCode) {
            $console->success($statusMessage);

            $rssFeedPath  = $input->getArgument('path');
            $bytesWritten = \file_put_contents($rssFeedPath, $response->getContent());

            if (false !== $bytesWritten) {
                $console->success(\sprintf('RSS feed successfully generated at path "%s", %d bytes written.', $rssFeedPath, $bytesWritten));
            } else {
                $console->error(\sprintf('RSS feed could not be generated at path "%s"!', $rssFeedPath));
            }

            return $bytesWritten
                ? Command::SUCCESS
                : Command::FAILURE;
        } else {
            $console->error($statusMessage);
        }

        return Command::SUCCESS;
    }
}
