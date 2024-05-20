<?php

namespace App\Controller\Admin;

use App\Entity\Keywords;
use App\Form\AddKeywordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/keywords', name: 'app_admin_keywords_')]
class KeywordsController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }
    
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/keywords/index.html.twig', [
            'controller_name' => 'KeywordsController',
        ]);
    }

    #[Route('/ajouter', name: 'add')]
    public function addKeyword(
        Request $request, 
        EntityManagerInterface $em,
        SluggerInterface $slugguer
        ): Response
    {
        $keyword = new Keywords();

        $keywordForm = $this->createForm(AddKeywordFormType::class, $keyword);

        $keywordForm->handleRequest($request);

        if($keywordForm->isSubmitted() && $keywordForm->isValid())
        {
            $slugguer = strtolower($slugguer->slug($keyword->getName()));
            
            $keyword->setSlug($slugguer);

            $em->persist($keyword);
            $em->flush();
 
            $this->addFlash('success', $this->translator->trans('Keyword add successfully'));
            return $this->redirectToRoute('app_admin_keywords_add');
        }
        
        return $this->render('admin/keywords/add.html.twig', [
            'keywordForm' => $keywordForm->createView(),
        ]);
    }
}
