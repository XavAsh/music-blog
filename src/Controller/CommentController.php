<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

       #[Route('/articles/{id}/comments', name: 'api_comments_index', methods: ['GET'])]
    public function index(int $id): JsonResponse
    {
        $article = $this->entityManager->getRepository(Article::class)->find($id);
        
        if (!$article) {
            throw new NotFoundHttpException('Article not found');
        }
    
        $comments = $article->getComments();
        $data = [];
    
        foreach ($comments as $comment) {
            $data[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'author' => $comment->getUser()?->getEmail(),
                'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $comment->getUpdatedAt()->format('Y-m-d H:i:s')
            ];
        }
    
        return $this->json($data);
    }

    #[Route('/articles/{id}/comments', name: 'api_comments_new', methods: ['POST'])]
    public function new(Request $request, Article $article): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedException('You must be logged in to post a comment.');
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['content'])) {
            return $this->json(['error' => 'Content is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $now = new \DateTimeImmutable();

        $comment->setContent($data['content']);
        $comment->setUser($user);
        $comment->setArticle($article);
        $comment->setCreatedAt($now);
        $comment->setUpdatedAt($now);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json([
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'author' => $comment->getUser()?->getEmail(),
            'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $comment->getUpdatedAt()->format('Y-m-d H:i:s')
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/comments/{id}', name: 'api_comments_edit', methods: ['PUT'])]
    public function edit(Request $request, Comment $comment): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedException('You must be logged in to edit a comment.');
        }

        // Check if user is the author or has ROLE_ADMIN
        if ($comment->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You can only edit your own comments.');
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['content'])) {
            $comment->setContent($data['content']);
            $comment->setUpdatedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        return $this->json([
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'author' => $comment->getUser()?->getEmail(),
            'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $comment->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/comments/{id}', name: 'api_comments_delete', methods: ['DELETE'])]
    public function delete(Comment $comment): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedException('You must be logged in to delete a comment.');
        }

        // Check if user is the author or has ROLE_ADMIN
        if ($comment->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You can only delete your own comments.');
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}