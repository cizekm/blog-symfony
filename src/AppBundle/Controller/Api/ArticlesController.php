<?php

namespace AppBundle\Controller\Api;

use AppBundle\Model\Article;
use AppBundle\Model\Tag;
use AppBundle\Repository\ArticleRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Rest\Route("/api")
 */
class ArticlesController extends FOSRestController
{
	/**
	 * @Rest\Route("/articles")
	 * @return Response
	 */
	public function getArticlesAction(): Response
	{
		$data = [];

		foreach ($this->getArticleRepository()->getLatestPublishedArticles() as $article) {
			$data[] = [
				'id' => $article->getId(),
				'title' => $article->getTitle(),
				'url' => $this->getArticleUrl($article),
				'publishedTimestamp' => $article->getPublishedTimestampString('Y-m-d H:i:s'),
				'visible' => $article->isVisible(), // always true, API provides only visible articles
				'viewsCnt' => $article->getViewsCnt()
			];
		}

		return $this->json($data);
	}

	/**
	 * @Rest\Route("/article/{id}", requirements={"id": "\d+"})
	 * @param int $id
	 * @return Response
	 */
	public function getArticleAction(int $id): Response
	{
		/** @var Article $article */
		if ($article = $this->getArticleRepository()->find($id)) {
			if ($article->isPublic()) {
				$data = [
					'id' => $article->getId(),
					'title' => $article->getTitle(),
					'url' => $this->getArticleUrl($article),
					'content' => $article->getContent(),
					'publishedTimestamp' => $article->getPublishedTimestampString('Y-m-d H:i:s'),
					'visible' => $article->isVisible(),
					'viewsCnt' => $article->getViewsCnt(),
					'tags' => $article->getTags()->map(
						function (Tag $tag) {
							return $tag->getTitle();
						}
					)->toArray()
				];

				$this->getArticleRepository()->logArticleView($article);
			} else {
				$data = [
					'error' => 'Article is not public'
				];
			}
		} else {
			$data = [
				'error' => 'Article not found'
			];
		}

		return $this->json($data);
	}

	/**
	 * @return ArticleRepository
	 */
	protected function getArticleRepository(): ArticleRepository
	{
		return $this->getDoctrine()->getRepository(Article::class);
	}

	/**
	 * @param Article $article
	 * @return string
	 */
	protected function getArticleUrl(Article $article): string
	{
		return $this->generateUrl(
			'blog_article_detail',
			['articleUrl' => $article->getUrl()],
			UrlGeneratorInterface::ABSOLUTE_URL
		);
	}
}
