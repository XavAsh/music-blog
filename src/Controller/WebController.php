<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\GeneratePdfMessage;
use App\Service\SpotifyApi;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class WebController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpotifyApi $spotifyApi

    ) {}

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
    }

    #[Route('/articles', name: 'app_article_index')]  
    public function index(): Response                 
    {
        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        return $this->render('article/index.html.twig', [
            'articles' => $articles
        ]);
    }


    #[Route('/articles/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $article = new Article();
        $article->setAuthor($this->getUser());
        $article->setCreatedAt(new \DateTimeImmutable());
        $article->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $this->addFlash('success', 'Article created successfully!');
            return $this->redirectToRoute('app_article_index');
        }

        return $this->render('article/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/articles/{id}', name: 'app_article_show')]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article
        ]);
    }

   

    #[Route('/articles/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Article $article): Response
    {
        if ($article->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only edit your own articles.');
        }
    
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $article->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Article updated successfully!');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }
    
        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form
        ]);
    }

    #[Route('/articles/{id}/delete', name: 'app_article_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Article $article): Response
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_articles');
    }

    #[Route('/articles/{id}/pdf', name: 'app_article_pdf')]
    #[IsGranted('ROLE_USER')]
    public function generatePdf(Article $article, MessageBusInterface $messageBus): Response
    {
        $pdfPath = 'pdfs/article_' . $article->getId() . '.pdf';
        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $pdfPath;
    
        if (!file_exists($fullPath)) {
            $messageBus->dispatch(new GeneratePdfMessage($article->getId()));
            $this->addFlash('info', 'PDF generation started. Please wait a few seconds and try again.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }
    
        return $this->redirect('/' . $pdfPath);
    }

    #[Route('/spotify/artists/{id}/pdf', name: 'app_spotify_artist_pdf')]
    #[IsGranted('ROLE_USER')]
    public function generateSpotifyArtistPdf(string $id, MessageBusInterface $messageBus): Response
    {
        $pdfPath = 'pdfs/spotify_artist_' . $id . '.pdf';
        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $pdfPath;

        if (!file_exists($fullPath)) {
            $messageBus->dispatch(new GenerateSpotifyPdfMessage($id));
            $this->addFlash('info', 'PDF generation started. Please wait a few seconds and try again.');
            return $this->redirectToRoute('app_spotify_artist_show', ['id' => $id]);
        }

        return $this->redirect('/' . $pdfPath);
    }

    #[Route('/spotify/artists/{id}', name: 'app_spotify_artist_show')]
    #[IsGranted('ROLE_USER')]
    public function showSpotifyArtist(string $id): Response
    {
        try {
            $artist = $this->spotifyApi->getArtist($id);
            $topTracks = $this->spotifyApi->getArtistTopTracks($id, ['country' => 'FR']);
            
            return $this->render('spotify/show.html.twig', [
                'artist' => $artist,
                'topTracks' => $topTracks->tracks
            ]);
        } catch (\Exception $e) {
            // Add this line to log the actual error
            dump($e->getMessage()); // This will show in the debug toolbar
            
            $this->addFlash('error', 'Artist not found: ' . $e->getMessage());
            return $this->redirectToRoute('app_spotify_search');
        }
    }
    
    #[Route('/spotify', name: 'app_spotify_search')]
    #[IsGranted('ROLE_USER')]
    public function spotifySearch(): Response
    {
        return $this->render('spotify/index.html.twig');
    }
}
