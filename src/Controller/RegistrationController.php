<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\JWTService;
use Doctrine\ORM\EntityManager;
use App\Service\SendEmailService;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator){}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        UserAuthenticatorInterface $userAuthenticator, 
        UsersAuthenticator $authenticator, 
        EntityManagerInterface $entityManager, 
        JWTService $jwt, 
        SendEmailService $mail
        ): Response

    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            // Générer le token
            //Header
            $header = [
                'type' => 'JWT',
                'alg' => 'HS256'
            ];

            //Payload
            $payload = [
                'user_id' => $user->getId()
            ];

            //On génère le token
            $token = $jwt->generate($header,$payload, $this->getParameter('app.jwtsecret'));
            // Envoyer l'e-mail
            $mail->send(
                'no-reply@symblog.test',
                $user->getEmail(),
                'Activation de votre compte sur le site SymBlog',
                'register',
                compact('user', 'token') // ['user' => $user, 'token'=>$token]
            );

            $this->addFlash('success', $this->translator->trans('Registered user, please click on the link to confirm your e-mail address'));

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verif_user/{token}',name: 'verif_user')]
    public function verifUser($token, JWTService $jwt, UsersRepository $usersRepository, EntityManagerInterface $em): Response
    {
        // verification token valide (cohérant, pas expiré et signature correcte)

        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret')))
        {
            // Le token est valide
            // On récupère les données (payload)

            $payload = $jwt->getPayload($token);

            // On récupère le user
            $user = $usersRepository->find($payload['user_id']);

            // On vérifie qu'on a bien user et qu'il n'est pas déjà activé
            if($user && !$user->isVerified())
            {
                $user->setVerified(true);
                $em->flush();

                $this->addFlash('success', $this->translator->trans('User activated'));
                return $this->redirectToRoute('app_main');
            }
        }

        $this->addFlash('danger', $this->translator->trans('The token is invalid or has expired'));
        return $this->redirectToRoute('app_login');

    }
}
