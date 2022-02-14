<?php
/*
 * MIT License
 *
 * Copyright (c) 2021-2022 machinateur
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
use Exception;
use Github\AuthMethod;
use Github\Client as GitHubClient;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as ControllerAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;
use Twig\Error\Error as TwigError;

/**
 * Class CommentController
 *
 * @Route("/comments", name="comment_", schemes={
 *     "https",
 * }, priority=10)
 *
 * @package App\Controller
 */
class CommentController extends ControllerAbstract
{
    public const SESSION_KEY_TOKEN = 'app.comments.github.access_token';
    private const SESSION_KEY_STATE = 'app.comments.auth.state';
    private const SESSION_KEY_ORIGIN = 'app.comments.auth.origin';

    private const URL_TO_AUTHORIZE = 'https://github.com/login/oauth/authorize';
    private const URL_TO_ACCESS_TOKEN = 'https://github.com/login/oauth/access_token';

    private CacheItemPoolInterface $cache;

    private GitHubClient $client;

    /**
     * CommentController constructor.
     * @param CacheItemPoolInterface $commentCache
     * @param GitHubClient $client
     */
    public function __construct(CacheItemPoolInterface $commentCache, GitHubClient $client)
    {
        $this->cache = $commentCache;
        $this->client = $client;
    }

    /**
     * @Route("/{subjectId}", name="list_comments", requirements={
     *     "subjectId" = "^[a-z0-9]{32}$",
     * }, methods={
     *     "GET",
     * }, condition="request.isXmlHttpRequest() or true == '%kernel.debug%'")
     *
     * @see https://regex101.com/r/hA5Cha/1
     *
     * @param string $subjectId
     * @return Response
     */
    public function list_comments(Request $request, string $subjectId): Response
    {
        // Use cache to save on rate limits, even though they would probably suffice.

        /** @var array|array[] $comments */
        $comments = $this->cache->get("app.comments.{$subjectId}", function (ItemInterface $item) use ($subjectId): array {
            // Make it expire after one hour.
            $item
                ->expiresAfter(
                    new DateInterval('PT1H')
                );
            // No tags for now, they won't work well anyway with fs.
            //$item
            //    ->tag('comments');

            // Load the gist comments from given subject id.
            /** @var array|array[] $comments */
            return $comments = $this->client->gists()
                ->comments()
                ->all($subjectId);
        });

        // Render comments server side, then return them.
        return $this->renderListOfComments($subjectId, $comments);
    }

    /**
     * @Route("/{subjectId}/new", name="add_new_comment", requirements={
     *     "subjectId" = "^[a-z0-9]{32}$",
     * }, methods={
     *     "POST",
     *     "PUT",
     * }, condition="request.isXmlHttpRequest() or true == '%kernel.debug%'")
     *
     * @see https://regex101.com/r/hA5Cha/1
     *
     * @param Request $request
     * @param string $subjectId
     * @return JsonResponse
     */
    public function add_new_comment(Request $request, string $subjectId): Response
    {
        // Only accept the request when the header says "Content-Type: text/markdown".
        if ('text/markdown' !== $request->headers->get('Content-Type')) {
            throw new BadRequestHttpException('That\'s not markdown, ay. Mind your headers.');
        }

        // Verify csrf-token protection with 'app.comments.{$subjectId}'.
        $csrfToken = $request->headers->get('csrf-token');

        if (!$this->isCsrfTokenValid("app.comments.{$subjectId}", $csrfToken)) {
            throw new AccessDeniedHttpException('Sorry, your csrf-token seems to be invalid. Could you try again?');
        }

        $session = $request->getSession();

        if ($request->hasPreviousSession()) {
            // Check access token existence.
            $isUserUnauthenticated = null ===
                ($token = $session->get(self::SESSION_KEY_TOKEN));
        } else {
            $isUserUnauthenticated = true;
        }

        if ($isUserUnauthenticated) {
            throw new AccessDeniedHttpException('Sorry, I\'m not allowed to add a comment on your behalf.');
        } else {
            // Authenticate user to be able to post a comment on their behalf.
            $this->client->authenticate($token, '', AuthMethod::ACCESS_TOKEN);
        }

        // Take the markdown.
        $body = $request->getContent(false);

        // Submit the comment body.
        $comment = $this->client->gists()
            ->comments()
            ->create($subjectId, $body);

        // Invalidate cache, as there is a new comment.
        $this->cache->delete("app.comments.{$subjectId}");

        // Return the new comment. Use null as first element to add new border-top style.
        return $this->renderListOfComments($subjectId, [
            null,
            $comment,
        ]);
    }

    /**
     * @param string $subjectId
     * @param array|array[] $comments
     * @return Response
     */
    private function renderListOfComments(string $subjectId, array $comments): Response
    {
        $view = '_comments.html.twig';

        /** @var Environment $twig */
        $twig = $this->container->get('twig');

        $context = [];
        $context['subject_id'] = $subjectId;
        $context['comments'] = $comments;

        try {
            $content = $twig->render($view, $context);
        } catch (TwigError $error) {
            // Return 400 in case the template yields an error.
            throw new BadRequestHttpException('', $error);
        }

        return new Response($content, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * @Route("/auth", name="authenticate_user", methods={
     *     "GET",
     *     "POST",
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function authenticate_user(Request $request): Response
    {
        // Set the scope.
        $scope = [];
        // See https://docs.github.com/en/developers/apps/building-oauth-apps/scopes-for-oauth-apps#available-scopes for
        //  detailed information on what each scope does.
        $scope[] = 'gist';

        try {
            $state = random_bytes(64);
            $state = @bin2hex($state);
        } catch (Exception $exception) {
            throw new HttpException(500, 'Seems like there was an error. Please let me sort this out.', $exception);
        }

        // Prepare for takeoff.
        $session = $request->getSession();
        $session->set(self::SESSION_KEY_STATE, $state);

        // Create an absolute origin url to redirect after finishing authentication.
        if ($request->isMethod(Request::METHOD_POST) && $request->request->has('origin')) {
            // Verify csrf-token protection with 'app.comments.auth'.
            $csrfToken = $request->request->get('csrf-token');

            if (!$this->isCsrfTokenValid('app.comments.auth', $csrfToken)) {
                throw new AccessDeniedHttpException('Sorry, your csrf-token seems to be invalid. Could you try again?');
            }

            // Don't worry, this will get checked for validity later on.
            $origin = $request->request->get('origin');
        } else {
            $origin = $this->generateUrl('index_view', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $isOriginInvalid =
            ($request->getHost() !== parse_url($origin, PHP_URL_HOST));

        if ($isOriginInvalid) {
            throw new BadRequestHttpException('There seems to be a problem with your request origin.');
        }

        $session->set(self::SESSION_KEY_ORIGIN, $origin);

        // Pack everything up.
        $dataPackage = [
            'client_id' => $this->getParameter('github.username'),
            'scope' => implode(' ', $scope),
            'state' => $state,
        ];

        // Remove scope, in case it is empty at this point.
        if (0 === count($scope)) {
            unset($dataPackage['scope']);
        }

        // Generate the target url.
        $url = self::URL_TO_AUTHORIZE
            . '?' . http_build_query($dataPackage, '', '&', PHP_QUERY_RFC1738);

        return new RedirectResponse($url);
    }

    /**
     * @Route("/auth/callback", name="authentication_callback", methods={
     *     "GET",
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function authentication_callback(Request $request): Response
    {
        /** @var string $code */
        $code = $request->query->get('code');

        $session = $request->getSession();

        // Compare state query parameter to the respective session value.
        $state = $request->query->get('state');

        /** @var string|null $stateOriginal */
        $stateOriginal = $session->get(self::SESSION_KEY_STATE);
        $session->remove(self::SESSION_KEY_STATE);

        $isStateInvalid =
            (is_null($stateOriginal) || is_null($state) || false === hash_equals($stateOriginal, $state));

        if ($isStateInvalid) {
            throw new BadRequestHttpException('There seems to be a problem with your request.');
        }

        // Compare session origin value host to the current request host.
        $origin = $session->get(self::SESSION_KEY_ORIGIN);
        $session->remove(self::SESSION_KEY_ORIGIN);

        $isOriginInvalid =
            ($request->getHost() !== parse_url($origin, PHP_URL_HOST));

        if ($isOriginInvalid) {
            throw new BadRequestHttpException('There seems to be a problem with your request origin.');
        }

        // Pack data to exchange the code for an access token.
        $dataPackage = [
            'client_id' => $this->getParameter('github.username'),
            'client_secret' => $this->getParameter('github.secret'),
            'code' => $code,
        ];

        // Request the access token.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL_TO_ACCESS_TOKEN);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataPackage);

        $responseContent = curl_exec($ch);
        /** @var array $response */
        parse_str($responseContent, $response);

        curl_close($ch);

        // Check response content.
        $isResponseInvalid =
            (!isset($response['access_token']) || !isset($response['scope']) || !isset($response['token_type']));

        if ($isResponseInvalid) {
            throw new BadRequestHttpException('There seems to be a problem with your request.');
        }

        // Save the access token.
        $session->set(self::SESSION_KEY_TOKEN, $response['access_token']);

        return new RedirectResponse($origin);
    }

    /**
     * @Route("/auth/no", name="unauthenticate_user", methods={
     *     "GET",
     *     "POST",
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function unauthenticate_user(Request $request): Response
    {
        // Create an absolute origin url to redirect after finishing authentication.
        if ($request->isMethod(Request::METHOD_POST) && $request->request->has('origin')) {
            // Verify csrf-token protection with 'app.comments.unauth'.
            $csrfToken = $request->request->get('csrf-token');

            if (!$this->isCsrfTokenValid('app.comments.unauth', $csrfToken)) {
                throw new AccessDeniedHttpException('Sorry, your csrf-token seems to be invalid. Could you try again?');
            }

            // Don't worry, this will get checked for validity later on.
            $origin = $request->request->get('origin');
        } else {
            $origin = $this->generateUrl('index_view', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $isOriginInvalid =
            ($request->getHost() !== parse_url($origin, PHP_URL_HOST));

        if ($isOriginInvalid) {
            throw new BadRequestHttpException('There seems to be a problem with your request origin.');
        }

        $session = $request->getSession();
        // Invalidate, e.g. clear, the session.
        $session->invalidate();

        return new RedirectResponse($origin);
    }
}
