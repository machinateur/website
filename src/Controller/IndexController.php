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

namespace App\Controller;

use DateInterval;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as ControllerAbstract;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\Error as TwigError;

/**
 * Class IndexController
 *
 * @Route("", name="index_", schemes={
 *     "https",
 * }, priority=0)
 *
 * @package App\Controller
 */
class IndexController extends ControllerAbstract
{
    private const DEFAULT_PATH = 'overview';

    private const EXPOSED_PARAMETERS = [
        // App Version
        'version',
        // Google Analytics
        'ga_measurement_id',
        // Google AdSense
        'ad_script',
        'ad_client',
        // Google AdSense Ads Configuration
        'ad_config',
    ];

    private readonly Environment $twig;

    private readonly CacheItemPoolInterface $cache;

    /**
     * IndexController constructor.
     *
     * @param Environment $twig
     * @param CacheItemPoolInterface $contentCache
     */
    public function __construct(Environment $twig, CacheItemPoolInterface $contentCache)
    {
        $this->twig  = $twig;
        $this->cache = $contentCache;
    }

    // TODO: Use attributes for routes.
    // TODO: Extract logic to make it reusable (i.e. for RSS feed).

    /**
     * @Route("/{path}", name="view", requirements={
     *     "path" = "^(?:\/?[a-z0-9]+(?:-[a-z0-9]+)*)+$",
     * }, methods={
     *     "GET",
     * })
     *
     * @see https://regex101.com/r/GRZ0vv/1
     *
     * @param Request $request
     * @param string|null $path
     * @return Response
     */
    public function view(Request $request, ?string $path = null): Response
    {
        if (null === $path) {
            // Redirect to default route.
            return $this->redirectToRoute('index_view', [
                'path' => static::DEFAULT_PATH,
            ], 302);
        }

        // TODO: Is content type resolution possible?
        $view = 'content/' . $path . '.html.twig';

        $twigLoader = $this->twig->getLoader();

        // Return 404 in case the path does not exist.
        if (false === $twigLoader->exists($view)) {
            throw new NotFoundHttpException();
        }

        $viewBasePath = $this->getParameter('twig.default_path');

        /** @var string[] $contextGlobals */
        $contextGlobals = \array_keys(
            $this->twig->mergeGlobals([])
        );

        $context = [];
        $context['current_path'] = $path;
        $context['current_view'] = $view;

        /** @var null|SplFileInfo[] $blogPosts */
        $blogPosts = null;
        /** @var string[] $blogPostsPathnames */
        $blogPostsPathnames = $this->cache->get('app.blog_posts',
            function (CacheItemInterface $item) use ($viewBasePath, &$blogPosts): array {
                // Make it expire after one day.
                $item->expiresAfter(
                    new \DateInterval('P1D')
                );

                /** @var SplFileInfo[] $blogPosts */
                $blogPosts = $this->getBlogPosts($viewBasePath);

                return \array_map(
                    function (SplFileInfo $fileInfo): string {
                        return $fileInfo->getRelativePathname();
                    }, $blogPosts
                );
            }
        );

        if (\is_null($blogPosts)) {
            if (true == $this->getParameter('kernel.debug')) {
                $blogPosts = $this->getBlogPosts($viewBasePath);
            } else {
                $blogPosts = \array_map(
                    static function (string $relativePathname) use ($viewBasePath): SplFileInfo {
                        return new SplFileInfo($viewBasePath . '/' . $relativePathname,
                            \dirname($relativePathname), $relativePathname);
                    }, $blogPostsPathnames
                );
            }
        }

        $context['blog_posts'] = $blogPosts;

        // Copy the parameters from container to context.
        foreach (static::EXPOSED_PARAMETERS as $parameter) {
            // Skip when the name is already taken, sorry.
            if (\array_key_exists($parameter, $context) || \in_array($parameter, $contextGlobals, true)) {
                continue;
            }

            // Get the parameter value or fallback to null.
            try {
                $parameterValue = $this->getParameter('app.' . $parameter);
            } catch (ParameterNotFoundException) {
                $parameterValue = null;
            }

            $context[$parameter] = $parameterValue;
        }

        // Render the template with context.
        try {
            $content = $this->twig->render($view, $context);
        } catch (TwigError $error) {
            // Return 400 in case the template yields an error.
            throw new BadRequestHttpException('0oops...', $error);
        }

        // Return the response.
        $response = new Response();
        $response->setContent($content);

        return $response;
    }

    /**
     * @param string|null $viewBasePath
     * @return SplFileInfo[]
     */
    private function getBlogPosts(?string $viewBasePath = null): array
    {
        if (null === $viewBasePath) {
            $viewBasePath = $this->getParameter('twig.default_path');
        }

        return \iterator_to_array(
            Finder::create()
                ->files()
                ->name('/^[a-z0-9]+(?:-[a-z0-9]+)*.html.twig$/')
                ->path('content/blog/')
                ->in(
                    $viewBasePath
                ),
            false
        );
    }
}
