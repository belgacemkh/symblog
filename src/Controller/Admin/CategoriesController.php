<?php

namespace App\Controller\Admin;

use App\Entity\Categories;
use App\Form\AddCategoriesFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/categories', name: 'app_admin_categories_')]
class CategoriesController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/categories/index.html.twig', [
            'controller_name' => 'CategoriesController',
        ]);
    }

    #[Route('/ajouter', name: 'add')]
    public function addCategorie(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugguer
    ): Response {
        $categorie = new Categories();

        $categorieForm = $this->createForm(AddCategoriesFormType::class, $categorie);

        $categorieForm->handleRequest($request);

        if ($categorieForm->isSubmitted() && $categorieForm->isValid()) {
            $slugguer = strtolower($slugguer->slug($categorie->getName()));

            $categorie->setSlug($slugguer);

            $em->persist($categorie);
            $em->flush();

            $this->addFlash('success', $this->translator->trans('Category add successfully'));
            return $this->redirectToRoute('app_admin_categories_add');
        }

        return $this->render('admin/categories/add.html.twig', [
            'categorieForm' => $categorieForm->createView(),
        ]);
    }
}
