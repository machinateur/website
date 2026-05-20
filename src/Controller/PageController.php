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

namespace Machinateur\Website\Controller;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Machinateur\Website\Service\BlogPostLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\Error as TwigError;

/**
 * Define actions related to content pages.
 *
 * @IgnoreAnnotation("lang")
 */
#[Route(path: '', name: 'page_', schemes: ['https'])]
class PageController extends AbstractController
{
    /**
     * The default path to render (i.e. home).
     *
     * @see self::home()
     *
     * @var string
     */
    private const DEFAULT_PATH = 'overview';

    /**
     * A list of exposed container parameters.
     *
     * @var array<string>
     */
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

    public function __construct(
        private readonly Environment    $twig,
        private readonly BlogPostLoader $loader,
    ) {}

    /**
     * The `home` action.
     *
     * It redirects to the {@see self::DEFAULT_PATH}.
     */
    #[Route(path: '', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('page_view', [
            'path' => self::DEFAULT_PATH,
        ]);
    }

    /**
     * The catch-all route that will look any other page content and render the twig application.
     */
    #[Route(path: '/{path}', name: 'view', requirements: [
        'path' => /** @lang PhpRegExp */ '^(?:\/?[a-z0-9]+(?:-[a-z0-9]+)*)+$',
    ], methods: [Request::METHOD_GET], priority: 0)]
    public function view(string $path): Response
    {
        $view = 'content/' . $path . '.html.twig';

        $twigLoader = $this->twig->getLoader();

        // Return 404 in case the path does not exist.
        if (false === $twigLoader->exists($view)) {
            throw new NotFoundHttpException();
        }

        $context                 = [];
        $context['current_path'] = $path;
        $context['current_view'] = $view;
        $context['blog_posts']   = $this->loader->load();

        $this->mergeGlobals($context);

        // Render the template with context.
        try {
            $content = $this->twig->render($view, $context);
        } catch (TwigError $error) {
            // Return 400 in case the template yields an error.
            throw new BadRequestHttpException('0oops...', $error);
        }

        // Return the response.
        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * Copy parameters from the container dependency based on {@see self::EXPOSED_PARAMETERS}.
     *
     * These parameters will made be available in the twig context under the same name.
     *
     * If the twig global context already contains a variable with the same name, the entry is skipped.
     *  Typically, this is only the case when the symfony twig configuration defines such a value.
     *
     * The dependency container is searched for parameters matching `app.{name}`.
     *  If not found, sets the parameter's value to null in the provided twig context.
     *
     * The provided context is modified by reference.
     */
    public function mergeGlobals(array &$context): void
    {
        /** @var array<string> $contextGlobals */
        $contextGlobals = \array_keys(
            $this->twig->getGlobals(),
        );

        // Copy the parameters from container to context.
        foreach (self::EXPOSED_PARAMETERS as $parameter) {
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
    }
}
