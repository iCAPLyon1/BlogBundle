<?php

namespace Icap\BlogBundle\Entity;

use Claroline\CoreBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Icap\BlogBundle\Utils\StringUtils;
use Icap\NotificationBundle\Entity\UserPickerContent;

/**
 * @ORM\Table(name="icap__blog_post")
 * @ORM\Entity(repositoryClass="Icap\BlogBundle\Repository\PostRepository")
 * @ORM\EntityListeners({"Icap\BlogBundle\Listener\PostListener"})
 * @ORM\HasLifecycleCallbacks()
 */
class Post extends Statusable
{
    /**
     * @var int $id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @var string $content
     *
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * @Gedmo\Slug(fields={"title"}, unique=true, updatable=false)
     * @ORM\Column(length=128, unique=true)
     */
    protected $slug;

    /**
     * @var \Datetime $creationDate
     *
     * @ORM\Column(type="datetime", name="creation_date")
     * @Gedmo\Timestampable(on="create")
     */
    protected $creationDate;

    /**
     * @var \Datetime $modificationDate
     *
     * @ORM\Column(type="datetime", name="modification_date", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "content"})
     */
    protected $modificationDate;

    /**
     * @var \Datetime $publicationDate
     *
     * @ORM\Column(type="datetime", name="publication_date", nullable=true)
     * @Gedmo\Timestampable(on="change", field="status", value="1")
     */
    protected $publicationDate;

    /**
     * @var int $viewCounter
     *
     * @ORM\Column(type="integer", options={"default": "0"})
     */
    protected $viewCounter = 0;

    /**
     * @var Comment
     *
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="post", cascade={"all"})
     */
    protected $comments;

    /**
     * @var User $author
     *
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $author;

    /**
     * @var Blog
     *
     * @ORM\ManyToOne(targetEntity="Icap\BlogBundle\Entity\Blog", inversedBy="posts")
     * @ORM\JoinColumn(name="blog_id", referencedColumnName="id")
     */
    protected $blog;

    /**
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Icap\BlogBundle\Entity\Tag", inversedBy="posts", cascade={"persist"})
     * @ORM\JoinTable(name="icap__blog_post_tag")
     */
    protected $tags;

    protected $userPicker = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tags     = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param  string $title
     *
     * @return Post
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param  string $content
     *
     * @return Post
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $url
     * @param string $text
     *
     * @return string
     */
    public function getShortContent($url, $text)
    {
        $readMoreText = sprintf('... <a href="%s" class="read_more">%s <span class="fa fa-long-arrow-right"></span></a>', $url, $text);
        return StringUtils::resumeHtml($this->content, 400, $readMoreText);
    }

    /**
     * Set slug
     *
     * @param  string $slug
     *
     * @return Post
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set creationDate
     *
     * @param  \DateTime $creationDate
     *
     * @return Post
     */
    public function setCreationDate(\DateTime $creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate
     *
     * @param  \DateTime $modificationDate
     *
     * @return Post
     */
    public function setModificationDate(\DateTime $modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set publicationDate
     *
     * @param \DateTime $publicationDate
     *
     * @return Post
     */
    public function setPublicationDate(\DateTime $publicationDate)
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    /**
     * Get publicationDate
     *
     * @return \DateTime
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * Add comments
     *
     * @param  Comment $comments
     *
     * @return Post
     */
    public function addComment(Comment $comments)
    {
        $this->comments[] = $comments;

        return $this;
    }

    /**
     * Remove comments
     *
     * @param Comment $comments
     *
     * @return Post
     */
    public function removeComment(Comment $comments)
    {
        $this->comments->removeElement($comments);

        return $this;
    }

    /***
     * Set comments
     *
     * @param  ArrayCollection $comments
     *
     * @return Post
     */
    public function setComments(ArrayCollection $comments)
    {
        /** @var \Icap\BlogBundle\Entity\Comment[] $comments */
        foreach ($comments as $comment) {
            $comment->setPost($this);
        }

        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return ArrayCollection|Comment[]
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set author
     *
     * @param  User $author
     *
     * @return Post
     */
    public function setAuthor(User $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set blog
     *
     * @param  Blog $blog
     *
     * @return Post
     */
    public function setBlog(Blog $blog = null)
    {
        $this->blog = $blog;

        return $this;
    }

    /**
     * Get blog
     *
     * @return Blog
     */
    public function getBlog()
    {
        return $this->blog;
    }

    /**
     * @param \Icap\BlogBundle\Entity\Tag[]|\Doctrine\Common\Collections\ArrayCollection $tags
     *
     * @return Post
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return \Icap\BlogBundle\Entity\Tag[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag $tag
     *
     * @return Post
     */
    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return Post
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * @param bool $countUnpublished
     *
     * @return int
     */
    public function countComments($countUnpublished = false)
    {
        $countComments = 0;

        if ($countUnpublished) {
            $countComments = $this->getComments()
                    ->count();
        } else {
            foreach ($this->getComments() as $comment) {
                if ($comment->isPublished()) {
                    $countComments++;
                }
            }
        }

        return $countComments;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        $isStatusPublished = parent::isPublished();

        $currentTimestamp = time();

        if ($isStatusPublished && (null !== $this->publicationDate && $currentTimestamp >= $this->publicationDate->getTimestamp())) {
            return true;
        }

        return false;
    }

    /**
     * @param int $viewCounter
     *
     * @return Post
     */
    public function setViewCounter($viewCounter)
    {
        $this->viewCounter = $viewCounter;

        return $this;
    }

    /**
     * @return int
     */
    public function getViewCounter()
    {
        return $this->viewCounter;
    }

    /**
     * @return Post
     */
    public function increaseViewCounter()
    {
        return $this->setViewCounter(++$this->viewCounter);
    }

    /**
     * @param UserPickerContent $userPicker
     * @return $this
     */
    public function setUserPicker(UserPickerContent $userPicker)
    {
        $this->userPicker = $userPicker;

        return $this;
    }

    /**
     * @return \Icap\NotificationBundle\Entity\UserPickerContent
     */
    public function getUserPicker()
    {
        return $this->userPicker;
    }
}
