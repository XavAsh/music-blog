<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@blog.com');
        // Remove setUsername call
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        $user = new User();
        $user->setEmail('user@blog.com');
        // Remove setUsername call
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, 'userpass'));
        $manager->persist($user);

        for ($i = 1; $i <= 3; $i++) {
            $article = new Article();
            $article->setTitle("Sample Article $i");
            $article->setContent("Content of article $i.");
            $article->setAuthor($admin);
            $article->setCreatedAt(new \DateTimeImmutable());
            $article->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($article);

            $comment = new Comment();
            $comment->setContent("Comment on article $i.");
            $comment->setArticle($article);
            $comment->setUser($user);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setUpdatedAt(new \DateTimeImmutable()); // Add this line
            $manager->persist($comment);
        }

        $manager->flush();
    }
}