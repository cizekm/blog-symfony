<?php

namespace AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ArticleRepository")
 * @ORM\Table(name="articles")
 */
class Article extends BaseModel
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 **/
	protected $id;

	/**
	 * @ORM\Column(type="string", length=200, name="title")
	 * @var string
	 * @Assert\NotBlank(message="Title is required")
	 * @Assert\Length(max=150, maxMessage="Title should not be longer than 150 characters")
	 **/
	protected $title = '';

	/**
	 * @ORM\Column(type="string", length=200, name="url", unique=true)
	 * @var string
	 **/
	protected $url = '';

	/**
	 * @ORM\Column(type="text", name="content")
	 * @var string
	 * @Assert\NotBlank(message="Article content should not be empty")
	 **/
	protected $content = '';

	/**
	 * @ORM\Column(type="datetime", name="published_timestamp")
	 * @var \DateTime
	 * @Assert\NotBlank(message="Published timestamp should not be empty")
	 **/
	protected $publishedTimestamp = null;

	/**
	 * @ORM\Column(type="boolean", name="visible")
	 **/
	protected $visible = false;

	/**
	 * @ORM\Column(type="integer", name="views_cnt")
	 */
	protected $viewsCnt = 0;


	/**
	 * @ORM\ManyToMany(targetEntity="Tag", inversedBy="articles", cascade={"persist", "remove"})
	 * @ORM\JoinTable(name="article_tags",
	 *      joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id", onDelete="CASCADE")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="tag_uid", referencedColumnName="uid", onDelete="CASCADE")}
	 * )
	 */
	protected $tags;


	public function __construct()
	{
		$this->tags = new ArrayCollection();
	}

	/**
	 * @ORM\PrePersist
	 */
	protected function checkPublishedTimestampBeforePersist(): void
	{
		if ($this->publishedTimestamp === null) {
			$this->publishedTimestamp = new \DateTime('now');
		}

		return;
	}

	/**
	 * @return int
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle(?string $title): self
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
	 * @param string $url
	 * @return $this
	 */
	public function setUrl(?string $url): self
	{
		$this->url = trim($url);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @param string $content
	 * @return $this
	 */
	public function setContent(?string $content): self
	{
		$this->content = trim($content);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->content;
	}

	/**
	 * @param bool $visible
	 * @return $this
	 */
	public function setVisible(bool $visible): self
	{
		$this->visible = $visible;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isVisible(): bool
	{
		return $this->visible;
	}

	/**
	 * @param \DateTime $publishedTimestamp
	 * @return $this
	 */
	public function setPublishedTimestamp(?\DateTime $publishedTimestamp): self
	{
		$this->publishedTimestamp = $publishedTimestamp;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getPublishedTimestamp(): \DateTime
	{
		return $this->publishedTimestamp;
	}

	/**
	 * @return bool
	 */
	public function isPublic(): bool
	{
		return $this->visible &&
			$this->publishedTimestamp !== null &&
			$this->publishedTimestamp <= new \DateTime('now');
	}

	/**
	 * @return int
	 */
	public function getViewsCnt(): int
	{
		return $this->viewsCnt;
	}

	/**
	 * @return $this
	 */
	public function increaseViewsCnt(): self
	{
		$this->viewsCnt++;

		return $this;
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function getPublishedTimestampString(string $format): string
	{
		return $this->publishedTimestamp->format($format);
	}

	/**
	 * @return $this
	 */
	public function removeTags(): self
	{
		$this->tags->clear();

		return $this;
	}

	/**
	 * @param Tag $tag
	 * @return $this
	 */
	public function addTag(Tag $tag): self
	{
		if (!$this->tags->contains($tag)) {
			$this->tags->add($tag);
		}

		return $this;
	}

	/**
	 * @return iterable|ArrayCollection|array|Tag[]
	 */
	public function getTags(): iterable
	{
		return $this->tags;
	}
}
