<?php

namespace AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TagRepository")
 * @ORM\Table(name="tags")
 */
class Tag extends BaseModel
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=100, name="uid")
	 **/
	protected $uid = '';

	/**
	 * @ORM\Column(type="string", length=100, name="title")
	 **/
	protected $title = '';

	/**
	 * @ORM\ManyToMany(targetEntity="Article", mappedBy="tags")
	 */
	protected $articles;


	public function __construct()
	{
		$this->articles = new ArrayCollection();
	}

	/**
	 * @param string $uid
	 * @return $this
	 */
	public function setUid(string $uid): self
	{
		$this->uid = trim($uid);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUid(): string
	{
		return $this->uid;
	}


	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle(string $title): self
	{
		$this->title = trim($title);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @ORM\PrePersist
	 * @ORM\PreUpdate
	 */
	protected function checkUidBeforePersistOrUpdate(): void
	{
		if (trim($this->uid) === '') {
			$this->uid = trim(Strings::webalize($this->title));
		}

		return;
	}
}
