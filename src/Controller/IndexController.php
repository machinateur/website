<?php
/*
 * MIT License
 *
 * Copyright (c) 2021-2021 machinateur
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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as ControllerAbstract;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Class IndexController
 *
 * @Route("", name="index_", schemes={
 *     "https",
 * })
 *
 * @package App\Controller
 */
class IndexController extends ControllerAbstract
{
    private static string $DEFAULT_PATH = 'overview';

    private static array $PARAMETER = [
        // Google Analytics
        'ga_measurement_id',
        // Google AdSense
        'ad_script',
        'ad_client',
    ];

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
     * @param array $context
     * @return Response
     */
    public function view(Request $request, ?string $path = null, array $context = []): Response
    {
        if (null === $path) {
            // Redirect to default route.
            return $this->redirectToRoute('index_view', [
                'path' => static::$DEFAULT_PATH,
            ], 302);
        }

        $view = 'content/' . $path . '.html.twig';

        /** @var Environment $templating */
        $templating = $this->container->get('twig');
        $templatingLoader = $templating->getLoader();

        // Return 404 in case the path does not exist.
        if (false === $templatingLoader->exists($view)) {
            throw new NotFoundHttpException();
        }

        /** @var string[] $contextGlobals */
        $contextGlobals = array_keys($templating->getGlobals());

        $context['current_path'] = $path;
        $context['current_view'] = $view;
        $context['blog_posts'] =
            iterator_to_array(
                Finder::create()
                    ->files()
                    ->name('/^[a-z0-9]+(?:-[a-z0-9]+)*.html.twig$/')
                    ->path('content/blog/')
                    ->in(
                        $this->getParameter('twig.default_path')
                    ),
                false
            );

        // Copy the parameters from container to context.
        foreach (static::$PARAMETER as $parameter) {
            // Skip when the name is already taken, sorry.
            if (array_key_exists($parameter, $context) || in_array($parameter, $contextGlobals, true)) {
                continue;
            }

            // Get the parameter value or fallback to null.
            try {
                $parameterValue = $this->getParameter("app.{$parameter}");
            } catch (ParameterNotFoundException $exception) {
                $parameterValue = null;
            }

            $context[$parameter] = $parameterValue;
        }

        // Render the template with context.
        try {
            $content = $templating->render($view, $context);
        } catch (Error $error) {
            // Return 400 in case the template yields an error.
            throw new BadRequestHttpException('', $error);
        }

        // Return the response.
        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
