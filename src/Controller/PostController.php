<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Following;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostFormType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{

    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    #[Route('/', name: 'index')]
    public function logout(): RedirectResponse
    {
        return $this->redirectToRoute('app_login');
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     *
     * @Route("/post", name = "app_post")
     */
    public function index(): Response
    {
        $posts = $this->doctrine->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $users = $this->doctrine->getRepository(User::class)->findAll();
        $following = $this->doctrine->getRepository(Following::class)->findAll();
        $followingArray = array();

        $i = 0;
        foreach ($following as $follower) {
            if ($follower->getUserId() == $this->getUser()->getId()) {
                $followingArray[$i++] = $follower->getFollowedId();
            }
        }

        return $this->render('post/index.html.twig', [
            'posts' => $posts, 'users' => $users, 'following' => $followingArray
        ]);
    }

    #[Route('add', name: 'add_post')]
    public function createPost(Request $request, SluggerInterface $slugger)
    {
        $post = new Post();

        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $this->setImage($form, $slugger, $post);
            $post->setUser($this->getUser()->getEmail());
            $em = $this->doctrine->getManager();
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('app_post');
        }

        return $this->renderForm('post/new.html.twig', [
            'form' => $form
        ]);
    }

    public function setImage($form, $slugger, $post)
    {
        $image = $form->get('Image')->getData();

        if ($image) {
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFileName = $slugger->slug($originalName);
            $newFileName = $safeFileName . '-' . uniqid() . '.' . $image->guessExtension();

            $image->move(
                $this->getParameter('image_dir'),
                $newFileName);
            $post->setImage($newFileName);
        }
    }

    #[Route('update', name: 'updatePost', options: ['expose' => true])]
    public function updatePost(Request $request, SluggerInterface $slugger)
    {
        $em = $this->doctrine->getManager();
        $id = $request->get('id');
        $post = $this->doctrine->getRepository(Post::class)->find($id);

        if ($request->isXmlHttpRequest()) {
            $title = $request->get('title');
            $content = $request->get('content');
            $post->setTitle($title);
            $post->setContent($content);
            $em->persist($post);
            $em->flush();
        }

        return new JsonResponse(['posts' => $post]);
    }


    #[Route('delete', name: 'deletePost', options: ['expose' => true])]
    public function deletePost(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $post = $this->doctrine->getRepository(Post::class)->find($request->get('id'));
            $posts = $this->doctrine->getRepository(Post::class)->findAll();
            $em = $this->doctrine->getManager();
            $em->remove($post);
            $em->flush();
        }

        return new JsonResponse(['posts' => $posts]);
    }

    #[Route('loadTweets', name: 'loadTweets', options: ['expose' => true])]
    public function loadTweets(Request $request)
    {
        $count = $request->get('count') * 5;

        $posts = $this->doctrine->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.id > :count')
            ->setParameter('count', $count)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(5)
            ->getQuery()->getArrayResult();

        return new JsonResponse(['posts' => $posts]);
    }

    #[Route('setLikes', name: 'setLikes', options: ['expose' => true])]
    public function setLikes(Request $request)
    {
        $em = $this->doctrine->getManager();
        $id = $request->get('id');
        $post = $this->doctrine->getRepository(Post::class)->find($id);

        $post->setLikes($post->getLikes() + 1);
        $userId = $this->getUser()->getId();
        $post->setLikesUser($userId . ',' . $post->getLikesUser());
        $em->persist($post);
        $em->flush();
        $likes = $post->getLikes();

        return new JsonResponse(['likes' => $likes]);
    }

    #[Route('showPost', name: 'showPost', options: ['expose' => true])]
    public function showPost(Request $request)
    {
        $id = $request->get('id');
        $post = $this->doctrine->getRepository(Post::class)->find($id);

        return $this->renderForm('post/show.html.twig',[
            'post' => $post, 'comments' => $post->getComments()
        ]);
    }

    #[Route('commentPost', name: 'commentPost', options: ['expose' => true])]
    public function commentPost(Request $request)
    {
        $id = $request->get('id');
        $title = $request->get('title');
        $content = $request->get('content');
        $post = $this->doctrine->getRepository(Post::class)->find($id);

        $comment = new Comment();
        $comment->setTitle($title);
        $comment->setContent($content);
        $comment->setPost($post);

        $em  = $this->doctrine->getManager();
        $em->persist($comment);
        $em->flush();

        return new JsonResponse(['commentTitle' => $comment->getTitle(),
            'commentContent' => $comment->getContent()]);
    }

}
