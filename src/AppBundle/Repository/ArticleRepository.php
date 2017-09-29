<?php

namespace AppBundle\Repository;

use AppBundle\Model\Article;
use AppBundle\Utils\Slugger;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class ArticleRepository extends EntityRepository
{
	/**
	 * @param int $page
	 * @param int $itemsPerPage
	 * @return Pagerfanta
	 */
	public function getLatestPublishedArticlesPaginator(int $page, int $itemsPerPage): Pagerfanta
	{
		$qb = $this->createLatestPublishedArticlesQueryBuilder();

		return $this->createPaginator($qb, $page, $itemsPerPage);
	}

	/**
	 * @return array|Article[]
	 */
	public function getLatestPublishedArticles(): array
	{
		$qb = $this->createLatestPublishedArticlesQueryBuilder();

		return $qb->getQuery()->getResult();
	}

	/**
	 * @return QueryBuilder
	 */
	protected function createLatestPublishedArticlesQueryBuilder(): QueryBuilder
	{
		$qb = $this->getEntityManager()->createQueryBuilder();
		$qb->select('a')
			->from(Article::class, 'a')
			->where($qb->expr()->andX(
				$qb->expr()->eq('a.visible', 1),
				$qb->expr()->lte('a.publishedTimestamp', ':now')
			))->orderBy('a.publishedTimestamp', 'desc');
		$qb->setParameter('now', new \DateTime('now'));

		return $qb;
	}

	/**
	 * @param QueryBuilder $qb
	 * @param int $page
	 * @param int $itemsPerPage
	 * @return Pagerfanta
	 */
	protected function createPaginator(QueryBuilder $qb, int $page, int $itemsPerPage): Pagerfanta
	{
		$paginator = new Pagerfanta(new DoctrineORMAdapter($qb));
		$paginator->setMaxPerPage($itemsPerPage);
		$paginator->setCurrentPage($page);

		return $paginator;
	}

	/**
	 * @param string $articleUrl
	 * @return Article|null
	 */
	public function findPublishedArticleByUrl(string $articleUrl): ?Article
	{
		$qb = $this->getEntityManager()->createQueryBuilder();
		$qb->select('a')
			->from(Article::class, 'a')
			->where($qb->expr()->andX(
				$qb->expr()->eq('a.visible', 1),
				$qb->expr()->like('a.url', ':articleUrl'),
				$qb->expr()->lte('a.publishedTimestamp', ':now')
			))->setMaxResults(1);
		$qb->setParameter('articleUrl', $articleUrl);
		$qb->setParameter('now', new \DateTime('now'));

		return $qb->getQuery()->getOneOrNullResult();
	}

	/**
	 * @param Article $article
	 * @return $this
	 */
	public function logArticleView(Article $article): self
	{
		$article->increaseViewsCnt();
		$this->saveArticle($article);

		return $this;
	}

	/**
	 * @param Article $article
	 * @param bool $flushImmediately
	 * @return $this
	 */
	public function saveArticle(Article $article, bool $flushImmediately = true): self
	{
		if (trim($article->getUrl()) === '') {
			$article->setUrl($this->getUniqueUrl($article->getTitle(), $article->getId()));
		}

		$this->getEntityManager()->persist($article);
		if ($flushImmediately) {
			$this->getEntityManager()->flush();
		}

		return $this;
	}

	/**
	 * @param string $orderBy
	 * @param string $orderDir
	 * @param int $page
	 * @param int $itemsPerPage
	 * @return Pagerfanta
	 */
	public function getAllArticlesPaginator(
		int $page,
		int $itemsPerPage,
		string $orderBy = null,
		string $orderDir = 'asc'
	): Pagerfanta {
		$qb = $this->getEntityManager()->createQueryBuilder();
		$qb->select('a')
			->from(Article::class, 'a');

		if (trim($orderBy) !== '' && trim($orderDir) !== '') {
			$qb->orderBy('a.'.$orderBy, $orderDir);
		}

		return $this->createPaginator($qb, $page, $itemsPerPage);
	}

	/**
	 * @param string $articleUrl
	 * @param int|null $excludeId
	 * @return bool
	 */
	public function urlExists(string $articleUrl, int $excludeId = null): bool
	{
		$criteria = [
			'url' => Slugger::generateSlug($articleUrl)
		];

		if ($excludeId !== null) {
			$criteria['id !='] = $excludeId;
		}

		return count($this->findBy($criteria)) > 0;
	}

	/**
	 * @param string $articleUrl
	 * @param int|null $articleId
	 * @return string
	 */
	public function getUniqueUrl(string $articleUrl, int $articleId = null): string
	{
		$articleUrlBase = $articleUrl = Slugger::generateSlug($articleUrl);

		$i = 2;
		while ($this->urlExists($articleUrl, $articleId)) {
			$articleUrl = $articleUrlBase.'-'.$i++;
		}

		return $articleUrl;
	}
}
