<?php

namespace Icap\BlogBundle\Controller;

use Claroline\CoreBundle\Entity\User;
use Icap\BlogBundle\Entity\Comment;
use Icap\BlogBundle\Entity\Post;
use Icap\BlogBundle\Entity\Blog;
use Icap\BlogBundle\Form\CommentType;
use Icap\BlogBundle\Form\PostType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class PostController extends Controller
{
    /**
     * @Route("/{blogId}/post/view/{postSlug}", name="icap_blog_post_view", requirements={"id" = "\d+"})
     *
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @ParamConverter("post", class="IcapBlogBundle:Post", options={"mapping": {"postSlug": "slug"}})
     * @Template()
     */
    public function viewAction(Request $request, Blog $blog, Post $post)
    {
        $this->checkAccess("OPEN", $blog);

        $user = $this->get('security.context')->getToken()->getUser();

        $this->dispatchPostReadEvent($blog, $post);

        $commentStatus = Comment::STATUS_UNPUBLISHED;
        if ($blog->isAutoPublishComment()) {
            $commentStatus = Comment::STATUS_PUBLISHED;
        }

        $comment = new Comment();
        $comment
            ->setPost($post)
            ->setAuthor($user)
            ->setStatus($commentStatus)
        ;

        $form = $this->createForm(new CommentType(), $comment);

        if ("POST" === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $translator = $this->get('translator');
                $flashBag = $this->get('session')->getFlashBag();
                $entityManager = $this->getDoctrine()->getManager();

                try {
                    $entityManager->persist($comment);
                    $entityManager->flush();

                    $this->dispatchCommentCreateEvent($blog, $post, $comment);

                    $flashBag->add('success', $translator->trans('icap_blog_comment_add_success', array(), 'icap_blog'));
                } catch (\Exception $exception) {
                    $flashBag->add('error', $translator->trans('icap_blog_comment_add_error', array(), 'icap_blog'));
                }

                return $this->redirect($this->generateUrl('icap_blog_post_view', array('blogId' => $blog->getId(), 'postSlug' => $post->getSlug())) . '#comments');
            }
        }

        return array(
            '_resource' => $blog,
            'user'      => $user,
            'post'      => $post,
            'form'      => $form->createView(),
            'archives'  => $this->getArchiveDatas($blog)
        );
    }
    /**
     * @Route("/{blogId}/post/new", name="icap_blog_post_new", requirements={"blogId" = "\d+"})
     *
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @Template()
     */
    public function newAction(Request $request, Blog $blog)
    {
        $this->checkAccess(array("EDIT", "POST"), $blog, "OR");

        $user = $this->get('security.context')->getToken()->getUser();

        $postStatus = Comment::STATUS_UNPUBLISHED;
        if ($blog->isAutoPublishPost()) {
            $postStatus = Comment::STATUS_PUBLISHED;
        }
        $post = new Post();
        $post
            ->setBlog($blog)
            ->setAuthor($this->getUser())
            ->setStatus($postStatus)
        ;

        $translator = $this->get('translator');

        $messages = array(
            'success' => $translator->trans('icap_blog_post_add_success', array(), 'icap_blog'),
            'error'   => $translator->trans('icap_blog_post_add_error', array(), 'icap_blog')
        );

        return $this->persistPost($request, $blog, $post, $user, 'create', $messages);
    }

    /**
     * @Route("/{blogId}/post/edit/{postSlug}", name="icap_blog_post_edit", requirements={"blogId" = "\d+"})
     *
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @ParamConverter("post", class="IcapBlogBundle:Post", options={"mapping": {"postSlug": "slug"}})
     * @Template()
     */
    public function editAction(Request $request, Blog $blog, Post $post)
    {
        $this->checkAccess(array("EDIT", "POST"), $blog, "OR");

        $user = $this->get('security.context')->getToken()->getUser();

        $translator = $this->get('translator');

        $messages = array(
            'success' => $translator->trans('icap_blog_post_edit_success', array(), 'icap_blog'),
            'error'   => $translator->trans('icap_blog_post_edit_error', array(), 'icap_blog')
        );

        return $this->persistPost($request, $blog, $post, $user, 'update', $messages);
    }

    protected function persistPost(Request $request, Blog $blog, Post $post, User $user, $action, array $messages)
    {
        /** @var \Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler $platformConfigHandler */
        $platformConfigHandler = $this->get('claroline.config.platform_config_handler');

        $form = $this->createForm($this->get('icap_blog.form.post'), $post, array('language' => $platformConfigHandler->getParameter('locale_language'), 'date_format' => $this->get('translator')->trans('date_form_format', array(), 'platform')));

        if ("POST" === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $flashBag = $this->get('session')->getFlashBag();
                $entityManager = $this->getDoctrine()->getManager();

                try {
                    $unitOfWork = $entityManager->getUnitOfWork();
                    $unitOfWork->computeChangeSets();
                    $changeSet = $unitOfWork->getEntityChangeSet($post);

                    $entityManager->persist($post);
                    $entityManager->flush();

                    if('create' === $action) {
                        $this->dispatchPostCreateEvent($blog, $post);
                    }
                    elseif('update' === $action) {
                        $this->dispatchPostUpdateEvent($blog, $post, $changeSet);
                    }
                    else {
                        throw new \InvalidArgumentException('Unknown action type for persisting post');
                    }

                    $flashBag->add('success', $messages['success']);
                } catch (\Exception $exception) {
                    $flashBag->add('error', $messages['error']);
                }

                return $this->redirect($this->generateUrl('icap_blog_view', array('blogId' => $blog->getId())));
            }
        }

        return array(
            '_resource' => $blog,
            'user'      => $user,
            'post'      => $post,
            'form'      => $form->createView(),
            'archives'  => $this->getArchiveDatas($blog)
        );
    }

    /**
     * @Route("/{blogId}/post/delete/{postSlug}", name="icap_blog_post_delete", requirements={"blogId" = "\d+"})
     *
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @ParamConverter("post", class="IcapBlogBundle:Post", options={"mapping": {"postSlug": "slug"}})
     * @Template()
     */
    public function deleteAction(Blog $blog, Post $post)
    {
        $this->checkAccess(array("EDIT", "POST"), $blog, "OR");

        $entityManager = $this->getDoctrine()->getManager();
        $translator    = $this->get('translator');
        $flashBag      = $this->get('session')->getFlashBag();

        try {
            $entityManager->remove($post);
            $entityManager->flush();

            $this->dispatchPostDeleteEvent($blog, $post);

            $flashBag->add('success', $translator->trans('icap_blog_post_delete_success', array(), 'icap_blog'));
        } catch (\Exception $exception) {
            $flashBag->add('error', $translator->trans('icap_blog_post_delete_error', array(), 'icap_blog'));
        }

        return $this->redirect($this->generateUrl('icap_blog_view', array('blogId' => $blog->getId())));
    }

    /**
     * @Route("/{blogId}/post/publish/{postSlug}", name="icap_blog_post_publish", requirements={"blogId" = "\d+"})
     *
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @ParamConverter("post", class="IcapBlogBundle:Post", options={"mapping": {"postSlug": "slug"}})
     * @Template()
     */
    public function publishAction(Blog $blog, Post $post)
    {
        $post->publish();

        $translator = $this->get('translator');

        $messages   = array(
            'success' => $translator->trans('icap_blog_post_publish_success', array(), 'icap_blog'),
            'error'   => $translator->trans('icap_blog_post_publish_error', array(), 'icap_blog')
        );

        return $this->changePublishStatus($blog, $post, $messages);
    }

    /**
     * @Route("/{blogId}/post/unpublish/{postSlug}", name="icap_blog_post_unpublish", requirements={"blogId" = "\d+"})
     *
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @ParamConverter("post", class="IcapBlogBundle:Post", options={"mapping": {"postSlug": "slug"}})
     * @Template()
     */
    public function unpublishAction(Blog $blog, Post $post)
    {
        $post->unpublish();

        $translator = $this->get('translator');

        $messages   = array(
            'success' => $translator->trans('icap_blog_post_unpublish_success', array(), 'icap_blog'),
            'error'   => $translator->trans('icap_blog_post_unpublish_error', array(), 'icap_blog')
        );

        return $this->changePublishStatus($blog, $post, $messages);
    }

    /**
     * @param Blog  $blog
     * @param Post  $post
     * @param array $messages
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function changePublishStatus(Blog $blog, Post $post, array $messages)
    {
        $this->checkAccess("EDIT", $blog);

        $entityManager = $this->getDoctrine()->getManager();
        $flashBag      = $this->get('session')->getFlashBag();

        try {
            $entityManager->persist($post);
            $entityManager->flush();

            $flashBag->add('success', $messages['success']);
        } catch (\Exception $exception) {
            $flashBag->add('error', $messages['error']);
        }

        return $this->redirect($this->generateUrl('icap_blog_view', array('blogId' => $blog->getId())));
    }
}
