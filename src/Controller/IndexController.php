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
            return $this->redirectToRoute('index_view', [
                'path' => 'overview',
            ], 302);
        }

        $view = 'content/' . $path . '.html.twig';

        /** @var Environment $templating */
        $templating = $this->container->get('twig');
        $templatingLoader = $templating->getLoader();

        if (false === $templatingLoader->exists($view)) {
            throw new NotFoundHttpException();
        }

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
        $context['ga_measurement_id'] = $this->getParameter('app.ga_measurement_id');

        try {
            $content = $templating->render($view, $context);
        } catch (Error $error) {
            throw new BadRequestHttpException('', $error);
        }

        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
