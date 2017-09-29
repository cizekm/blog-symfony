<?php

namespace AppBundle\Repository;

use AppBundle\Model\Article;
use AppBundle\Model\Tag;
use AppBundle\Utils\Slugger;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class TagRepository extends EntityRepository
{
	public function getUidFromTitle(string $title): string
	{
		return trim(Slugger::generateSlug($title));
	}

	/**
	 * @param string $title
	 * @return Tag
	 */
	public function findTagByTitle(string $title): ?Tag
	{
		$tags = $this->findBy(['title' => $title]);

		if (count($tags) === 1) {
			return reset($tags);
		}

		$tagUid = $this->getUidFromTitle($title);

		return $this->find($tagUid) ?: null;
	}

	/**
	 * @param Tag $tag
	 * @param bool $flushImmediately
	 * @return $this
	 */
	public function saveTag(Tag $tag, bool $flushImmediately = true): self
	{
		$this->getEntityManager()->persist($tag);
		if ($flushImmediately) {
			$this->getEntityManager()->flush();
		}

		return $this;
	}

	/**
	 * @param string $tagUid
	 * @return bool
	 */
	public function uidExists(string $tagUid): bool
	{
		return $this->find($tagUid) !== null;
	}

	/**
	 * @param string $tagUid
	 * @return string
	 */
	public function getUniqueUid(string $tagUid): string
	{
		$tagUidBase = $tagUid = Slugger::generateSlug($tagUid);

		$i = 2;
		while ($this->find($tagUid) !== null) {
			$tagUid = $tagUidBase.'-'.$i++;
		}

		return $tagUid;
	}
}
