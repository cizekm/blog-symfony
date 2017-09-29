<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Model\Article;
use AppBundle\Model\Tag;
use AppBundle\Repository\ArticleRepository;
use AppBundle\Repository\TagRepository;
use AppBundle\Utils\Slugger;
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

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ArticlesController extends Controller
{
	protected $itemsPerPage = 5;

	const ORDER_BY_TITLE = 'title';
	const ORDER_BY_PUBLISHED_TIMESTAMP = 'publishedTimestamp';
	const ORDER_BY_VIEWS_CNT = 'viewsCnt';
	const ORDER_BY_VISIBLE = 'visible';

	const POSSIBLE_ORDER_BY = [
		self::ORDER_BY_TITLE,
		self::ORDER_BY_PUBLISHED_TIMESTAMP,
		self::ORDER_BY_VIEWS_CNT,
		self::ORDER_BY_VISIBLE
	];

	const ORDER_DIR_ASC = 'asc';
	const ORDER_DIR_DESC = 'desc';

	const POSSIBLE_SORT_DIRS = [
		self::ORDER_DIR_ASC,
		self::ORDER_DIR_DESC
	];

	/**
	 * @Route("/articles/{page}", defaults={"page": "1"}, requirements={"page": "\d+"}, name="admin_articles")
	 * @Route("/", defaults={"page": "1"})
	 * @param int $page
	 * @param Request $request
	 * @return Response
	 */
	public function indexAction(int $page, Request $request): Response
	{
		$orderBy = trim($request->query->get('orderBy'));
		$orderDir = trim($request->query->get('orderDir'));
		if ($orderBy === '' || !in_array($orderBy, self::POSSIBLE_ORDER_BY)) {
			// by default we want to order artilcles from the latest to oldest
			$orderBy = self::ORDER_BY_PUBLISHED_TIMESTAMP;
			$orderDir = self::ORDER_DIR_DESC;
		} elseif (trim($orderDir) === '' || !in_array($orderDir, self::POSSIBLE_SORT_DIRS)) {
			//when no orderDir provided, we use asc as default for all columns except timestamp,
			// that we want to order from the latest by default
			$orderDir = $orderBy === self::ORDER_BY_PUBLISHED_TIMESTAMP ? self::ORDER_DIR_DESC : self::ORDER_DIR_ASC;
		}

		$articles = $this->getArticleRepository()->getAllArticlesPaginator(
			$page,
			$this->itemsPerPage,
			$orderBy,
			$orderDir
		);

		return $this->render('admin/articles/index.html.twig', [
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
			'articles' => $articles,
			'page' => $page,
			'orderBy' => $orderBy,
			'orderDir' => $orderDir,
			'orderDirAsc' => self::ORDER_DIR_ASC,
			'orderDirDesc' => self::ORDER_DIR_DESC,
			'inverseOrderDirs' => [
				self::ORDER_DIR_ASC => self::ORDER_DIR_DESC,
				self::ORDER_DIR_DESC => self::ORDER_DIR_ASC
			],
			'back_url' => $request->getRequestUri()
		]);
	}

	protected function getArticleForm(Article $article, bool $new, string $backUrl = null): FormInterface
	{
		$formBuilder = $this->createFormBuilder($article);

		if (!$new) {
			$formBuilder->add('id', HiddenType::class);
		}

		$tagTitles = implode(', ', $article->getTags()->map(function (Tag $tag) {
			return $tag->getTitle();
		})->toArray());

		$formBuilder
			->add('title', TextType::class, ['attr' => ['maxlength' =>  150]])
			->add('url', TextType::class, [
				'required' => false,
				'label' => 'URL',
				'attr' => ['placeholder' => 'auto generated']
			])
			->add('publishedTimestamp', DateTimeType::class)
			->add('visible', CheckboxType::class, ['required' => false])
			->add('content', TextareaType::class, ['required' => true, 'attr' => ['class' => 'editor']])
			->add('tagTitles', TextType::class, [
				'mapped' => false,
				'required' => false,
				'data' => $tagTitles,
				'label' => 'Tags (comma separated)'
			])
			->add('backUrl', HiddenType::class, ['mapped' => false, 'data' => $backUrl])
			->add('save', SubmitType::class, ['label' => 'Save article']);

		return $formBuilder->getForm();
	}

	/**
	 * @Route("/articles/add", name="admin_articles_add")
	 * @param Request $request
	 * @return Response
	 * @internal param string $back
	 */
	public function addAction(Request $request): Response
	{
		$article = new Article();

		$article->setPublishedTimestamp(new \DateTime('now'));
		$article->setVisible(true);

		$form = $this->getArticleForm($article, true, $request->query->get('back'));

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$this->saveArticleTags($article, $form->get('tagTitles')->getData());
			$this->getArticleRepository()->saveArticle($article);

			if ($backUrl = trim($form->get('backUrl')->getData())) {
				return $this->redirect($backUrl);
			} else {
				return $this->redirectToRoute('admin_articles');
			}
		}

		return $this->render('admin/articles/add.html.twig', [
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
			'form' => $form->createView(),
			'back_url' => trim($request->query->get('back')) ?: $this->generateUrl('admin_articles')
		]);
	}

	/**
	 * @Route("/articles/edit/{id}", requirements={"id": "\d+"}, name="admin_articles_edit")
	 * @param int $id
	 * @param Request $request
	 * @return Response
	 * @internal param string $back
	 */
	public function editAction(int $id, Request $request): Response
	{
		/** @var Article $article */
		$article = $this->getArticleRepository()->find($id);

		if (!$article) {
			return $this->redirectToRoute('admin_articles');
		}

		$form = $this->getArticleForm($article, true, $request->query->get('back'));

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$this->saveArticleTags($article, $form->get('tagTitles')->getData());
			$this->getArticleRepository()->saveArticle($article);

			if ($backUrl = trim($form->get('backUrl')->getData())) {
				return $this->redirect($backUrl);
			} else {
				return $this->redirectToRoute('admin_articles');
			}
		}

		return $this->render('admin/articles/edit.html.twig', [
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
			'form' => $form->createView(),
			'article' => $article,
			'back_url' => trim($request->query->get('back')) ?: $this->generateUrl('admin_articles')
		]);
	}

	protected function saveArticleTags(Article $article, string $tagTitlesStr): self
	{
		$tagTitles = explode(',', $tagTitlesStr);

		$article->removeTags();

		foreach ($tagTitles as $tagTitle) {
			if (trim($tagTitle) === '') {
				continue;
			}

			$tag = $this->getTagsRepository()->findTagByTitle($tagTitle);

			if ($tag === null) {
				$tag = new Tag();
				$tag->setTitle($tagTitle);
				$tag->setUid($this->getTagsRepository()->getUniqueUid(Slugger::generateSlug($tagTitle)));

				$this->getTagsRepository()->saveTag($tag, false);
			}

			$article->addTag($tag);
		}

		return $this;
	}

	/**
	 * @Route("/articles/change-visibility/{id}/{visible}", requirements={"id": "\d+", "visible": "[0,1]"}, name="admin_article_change_visibility")
	 * @param int $id
	 * @param int $visible
	 * @param Request $request
	 * @return Response
	 * @internal param string $back
	 */
	public function changeVisibilityAction(int $id, int $visible, Request $request): Response
	{
		/** @var Article $article */
		if ($article = $this->getArticleRepository()->find($id)) {
			$article->setVisible((bool)$visible);

			$this->getArticleRepository()->saveArticle($article);
		}

		if ($backUrl = trim($request->query->get('back'))) {
			return $this->redirect($backUrl);
		} else {
			return $this->redirectToRoute('admin_articles');
		}
	}

	/**
	 * @return ArticleRepository
	 */
	protected function getArticleRepository(): ArticleRepository
	{
		return $this->getDoctrine()->getRepository(Article::class);
	}

	/**
	 * @return TagRepository
	 */
	protected function getTagsRepository(): TagRepository
	{
		return $this->getDoctrine()->getRepository(Tag::class);
	}
}
