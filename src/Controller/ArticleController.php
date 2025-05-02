<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleForm;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Message\GeneratePdfMessage;
use Symfony\Component\HttpFoundation\Response;


#[Route('/api')]
class ArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

       #[Route('/articles', name: 'api_articles_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();
        $data = [];

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
                'author' => $article->getAuthor()?->getEmail(),
                'created_at' => $article->getCreatedAt()->format('Y-m-d H:i:s') ,
                'updated_at' => $article->getUpdatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json($data);
    }


    #[Route('/articles', name: 'api_articles_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $article = new Article();
        $now = new \DateTimeImmutable();
        
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setAuthor($this->security->getUser());
        $article->setCreatedAt($now);
        $article->setUpdatedAt($now);  
    
        $this->entityManager->persist($article);
        $this->entityManager->flush();
    
        return $this->json([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => $article->getAuthor()?->getEmail(),
            'created_at' => $article->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $article->getUpdatedAt()->format('Y-m-d H:i:s') 
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/articles/{id}', name: 'api_articles_show', methods: ['GET'])]
    public function show(Article $article): JsonResponse
    {
        return $this->json([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => $article->getAuthor()?->getEmail()
        ]);
    }

    #[Route('/articles/{id}', name: 'api_articles_edit', methods: ['PUT'])]
    public function edit(Request $request, Article $article): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }
        // Add this line to ensure created_at is set
        if (!$article->getCreatedAt()) {
            $article->setCreatedAt(new \DateTimeImmutable());
        }
        $article->setUpdatedAt(new \DateTimeImmutable()); 
    
        $this->entityManager->flush();
    
        return $this->json([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => $article->getAuthor()?->getEmail(),
            'created_at' => $article->getCreatedAt()->format('Y-m-d H:i:s') 
        ]);
    }

    #[Route('/articles/{id}', name: 'api_articles_delete', methods: ['DELETE'])]
    public function delete(Article $article): JsonResponse
    {
        try {
            // First, remove all related comments
            foreach ($article->getComments() as $comment) {
                $this->entityManager->remove($comment);
            }
            
            // Then remove the article
            $this->entityManager->remove($article);
            $this->entityManager->flush();
    
            return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Could not delete article: ' . $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/articles/{id}/pdf', name: 'api_article_pdf', methods: ['POST'])]
    public function generatePdf(int $id, MessageBusInterface $messageBus): JsonResponse
    {
        $article = $this->entityManager->getRepository(Article::class)->find($id);
        if (!$article) {
            throw new NotFoundHttpException('Article not found');
        }

        $messageBus->dispatch(new GeneratePdfMessage($id));

        return $this->json([
            'message' => 'PDF generation started',
            'pdf_url' => sprintf('/pdfs/article_%d.pdf', $id)
        ]);
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/web/articles', name: 'app_articles')]
    public function webIndex(): Response
    {
        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        return $this->render('article/index.html.twig', [
            'articles' => $articles
        ]);
    }

    #[Route('/web/articles/{id}', name: 'app_article_show')]
    public function webShow(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article
        ]);
    }
}