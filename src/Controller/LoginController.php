<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, AuthService $auth, UserRepository $userRepository, ManagerRegistry $doctrine): Response
    {
        $email = $request->get("email");
        $password = $request->get("password");

        if (is_null($email) || is_null($password)) {
            return new Response("No Email or Password", 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new Response("No User Found", 400);
        }

        if (!$auth->verifyPassword($user, $password)) {
            return new Response("Password Invalid", 403);
        }

        //Vérifier et rafraichir le token

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            "email" => $user->getEmail(),
            "roles" => $user->getRoles(),
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "contact" => $user->getContact(),
            "credits" => $user->getCredits(),
        ]);
    }

    #[Route('/signup', name: 'app_signup', methods: ['POST'])]
    public function signup(Request $request, UserRepository $userRepository, ManagerRegistry $doctrine): Response
    {
        $email = $request->get("email");
        $password = $request->get("password");
        $firstname = $request->get("firstname");
        $lastname = $request->get("lastname");
        $contact = $request->get("contact");
        $sponsorCode = $request->get("sponsorCode");

        $withEmail = $userRepository->findOneBy(['email' => $email]);

        if ($withEmail) {
            return new Response("Email Adress Already Token !", 400);
        }

        $code = substr($lastname, 0, 2) + substr($lastname, 0, 2) + substr($contact, strlen($contact) - 2, 2);

        $withCode = $userRepository->findOneBy(['code' => $code]);

        if ($withCode) {
            $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $oldCode = $code;

            for ($i = 0; $i < strlen($alphabet); $i++) {
                $code = $oldCode + $alphabet[$i];
                $withCode = $userRepository->findOneBy(['code' => $code]);

                if (!$withCode) {
                    break;
                }
            }
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setFirstName($firstname);
        $user->setLastname($lastname);
        $user->setContact($contact);
        $user->setCode($code);

        if ($sponsorCode !== '') {
            $sponsorUser = $userRepository->findOneBy(['code' => $code]);

            $user->setSponsor($sponsorUser);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            "email" => $user->getEmail(),
            "roles" => $user->getRoles(),
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "contact" => $user->getContact(),
            "code" => $user->getCode(),
            "sponsor" => $user->getSponsor(),
            "credits" => $user->getCredits(),
        ]);
    }
}
