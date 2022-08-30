<?php

namespace App\Controller;

use App\Entity\Following;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine) {}


    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $form = $this->createForm(ProfileType::class, $user);
        $form-> handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $user = $form->getData();

            $this->setImage($form, $slugger, $user);

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_post');
        }

        return $this->renderForm('profile.html.twig', [
            'form' => $form,
        ]);
    }

    public function setImage($form, $slugger, $user){
        $image = $form->get('image')->getData();

        if($image) {
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFileName = $slugger->slug($originalName);
            $newFileName = $safeFileName . '-' . uniqid() . '.' . $image->guessExtension();

            $image->move(
                $this->getParameter('image_dir'),
                $newFileName);
            $user-> setImage($newFileName);
        }
    }

    #[Route('/searchUser', name: 'searchUser', options: ['expose' => true])]
    public function searchUser(Request $request){
        $email = $request->get('email');
        $em = $this->doctrine->getManager();

        $qb = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.email LIKE :user')
            ->setParameter('user', '%'.$email.'%')
            ->getQuery()
            ->getResult();

        $emails = $qb[0]->getEmail();

        return new JsonResponse(['email' => $emails]);
    }

    #[Route('/getUsers', name: 'getUsers', options: ['expose' => true])]
    public function getUsers(){
        $users = $this->doctrine->getRepository(User::class)->findAll();

        return $this->render('user/index.html.twig',
            ['users' => $users]);
    }

    #[Route('/followUser', name: 'followUser', options: ['expose' => true])]
    public function followUser(Request $request){
        $id = $request->get('id');

        $followed = new Following();
        $followed->setUserId($this->getUser()->getId());
        $followed->setFollowedId($id);
        $em = $this->doctrine->getManager();
        $em->persist($followed);
        $em->flush();

        $user = $this->doctrine->getRepository(User::class)->find($id);
        $userEmail = $user->getEmail();
        $userImage = $user->getImage();
        $userId = $user->getId();

        return new JsonResponse(['userEmail'=> $userEmail, 'userImage'=>$userImage, 'userId'=>$userId]);
    }

}
