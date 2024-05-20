<?php

namespace App\Controller\Profile;

use App\Entity\Posts;
use App\Form\AddPostsFormType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/profile/posts', name: 'app_profile_posts_')]
class PostsController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator, private ParameterBagInterface $params)
    {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('profile/posts/index.html.twig', [
            'controller_name' => 'PostsController',
        ]);
    }

    #[Route('/ajouter', name: 'add')]
    public function addPost(
        Request $request,
        SluggerInterface $slugguer,
        EntityManagerInterface $em,
        UsersRepository $usersRepository
    ): Response {
        $post = new Posts();

        $form = $this->createForm(AddPostsFormType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image téléversée
            $file = $form->get('featuredImage')->getData();
            if ($file) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->params->get('category_pictures_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si quelque chose se passe pendant le téléversement du fichier
                    $this->addFlash('error', 'Failed to upload image.');
                }
                $post->setFeaturedImage($fileName);
            }

            // Génération du slug
            $slugguer = strtolower($slugguer->slug($post->getTitle()));
            $post->setSlug($slugguer);

            // Association de l'utilisateur (vous pouvez changer l'ID en fonction du contexte)
            $post->setUsers($usersRepository->find(1)); // Remplacez par l'utilisateur actuel si nécessaire
            // Les catégories et mots-clés sont automatiquement mappés par Symfony via le formulaire

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', $this->translator->trans('Post add successfully'));
            return $this->redirectToRoute('app_profile_posts_add');
        }

        return $this->render('profile/posts/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
