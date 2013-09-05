<?php

namespace ICAP\BlogBundle\Event\Log;

use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Event\Log\AbstractLogResourceEvent;
use Claroline\CoreBundle\Event\Log\LogNotRepeatableInterface;
use ICAP\BlogBundle\Entity\Blog;
use ICAP\BlogBundle\Entity\Post;

class LogPostReadEvent extends AbstractLogResourceEvent implements LogNotRepeatableInterface
{
    const ACTION = 'resource-icap_blog-post_read';

    public function __construct(Blog $blog, Post $post)
    {
        $details = array(
            'post' => array(
                'blog'  => $post->getBlog()->getId(),
                'title' => $post->getTitle(),
                'slug'  => $post->getSlug()
            )
        );

        parent::__construct($blog->getResourceNode(), $details);

        $this->isDisplayedInWorkspace(true);
    }

    public function getLogSignature()
    {
        return self::ACTION.'_' . $this->resource->getId();
    }
}