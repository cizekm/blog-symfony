<?php

namespace AppBundle\Controller;

use AppBundle\Model\Article;
use AppBundle\Repository\ArticleRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DefaultController extends Controller
{
	protected $itemsPerPage = 2;

    /**
     * @Route("/{page}", defaults={"page": "1"}, requirements={"page": "\d+"}, name="blog_index")
	 * @param int $page
	 * @param Request $request
	 * @return Response
     */
    public function indexAction(int $page, Request $request): Response
    {
    	$articles = $this->getArticleRepository()->getLatestPublishedArticlesPaginator($page, $this->itemsPerPage);

    	return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
			'articles' => $articles,
			'page' => $page,
			'back_url' => $request->getRequestUri()
        ]);
    }

	/**
	 * @Route("/article/{articleUrl}", name="blog_article_detail")
	 * @param string $articleUrl
	 * @param Request $request
	 * @return Response
	 */
    public function articleDetailAction(string $articleUrl, Request $request): Response
	{
		if (trim($articleUrl) === '' ||
			!($article = $this->getArticleRepository()->findPublishedArticleByUrl($articleUrl))
		) {
			throw new BadRequestHttpException();
		}

		$this->getArticleRepository()->logArticleView($article);

		return $this->render('default/article_detail.html.twig', [
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
			'article' => $article,
			'back_url' => trim($request->query->get('back')) ?: $this->generateUrl('blog_index')
		]);
	}

	protected function getArticleRepository(): ArticleRepository
	{
		return $this->getDoctrine()->getRepository(Article::class);
	}
}
