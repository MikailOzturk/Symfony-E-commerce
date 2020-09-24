<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, SettingRepository $settingRepository,CategoryRepository $categoryRepository): Response
    {

        $cats[0] = '<ul id="menu-v" class="cat_menu">';

        $data = $settingRepository->findAll();
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'cats' => $cats,
            'data' => $data
        ]);
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request): Response
    {
        $user = new User();
        if ($request->get("btn")) {

            $user->setEmail($request->get("email"));
            $user->setPassword($request->get("password"));
            $user->setStatus("true");
            $user->setRoles("ROLE_USER");
            $user->setName("Mikail");

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();


        }

        return $this->render('security/register.html.twig', []);
    }
}
