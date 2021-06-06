<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as ControllerAbstract;
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
 * @Route("", name="index_")
 *
 * @package App\Controller
 */
class IndexController extends ControllerAbstract
{
    /**
     * @Route("/{path}", name="index", methods={
     *     "GET",
     * }, requirements={
     *     "path" = "^(?:\/?[a-z0-9]+(?:-[a-z0-9]+)*)+$",
     * })
     *
     * @see https://regex101.com/r/GRZ0vv/1
     *
     * @param Request $request
     * @param string|null $path
     * @return Response
     */
    public function index(Request $request, ?string $path = null): Response
    {
        if (null === $path) {
            $view = 'content.html.twig';
        } else {
            $view = 'content/' . $path . '.html.twig';
        }

        /** @var Environment $templating */
        $templating = $this->container->get('twig');
        $templatingLoader = $templating->getLoader();

        if (false === $templatingLoader->exists($view)) {
            throw new NotFoundHttpException();
        }

        try {
            $content = $templating->render($view);
        } catch (Error $error) {
            throw new BadRequestHttpException('', $error);
        }

        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
