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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Define actions related to the RSS feed.
 *
 * @IgnoreAnnotation("lang")
 */
#[Route(path: '/feed', name: 'feed_', schemes: ['https'])]
class FeedController extends AbstractController
{
    public function __construct(
        private readonly PageController $pageController,
        private readonly BlogPostLoader $loader,
    ) {}

    /**
     * Display the RSS feed 2.0 XML document for available blog-posts.
     */
    #[Route(path: '', name: 'view', methods: [Request::METHOD_GET])]
    public function view(): Response
    {
        $context                 = [];
        $context['current_path'] = 'feed';
        $context['current_view'] = 'feed';
        $context['blog_posts']   = $this->loader->load();

        $this->pageController->mergeGlobals($context);

        $response = $this->render('_feed.xml.twig', $context);
        $response->headers->set('Content-Type', 'application/rss+xml');
        return $response;
    }
}
