<?php

namespace ICAP\BlogBundle\Entity;

use Claroline\CoreBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;

/**
 * @ORM\Entity
 * @ORM\Table(name="icap__blog")
 * @ORM\Entity(repositoryClass="ICAP\BlogBundle\Repository\BlogRepository")
 */
class Blog extends AbstractResource
{
    /**
     * @var string $name
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var Post[]
     *
     * @ORM\OneToMany(
     *     targetEntity="ICAP\BlogBundle\Entity\Post",
     *     mappedBy="blog"
     * )
     * @ORM\OrderBy({"creationDate" = "ASC"})
     */
    protected $posts;

    public function __construct()
    {
        $this->posts  = new ArrayCollection();
        $this->author = new User();
    }

    /**
     * @param ArrayCollection $posts
     *
     * @return Blog
     */
    public function setPosts(ArrayCollection $posts)
    {
        $this->posts = $posts;

        return $this;
    }

    /**
     * @return Post[]
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @param string $name
     *
     * @return Blog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


}
