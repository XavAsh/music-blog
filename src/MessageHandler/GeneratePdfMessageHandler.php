<?php

namespace App\MessageHandler;

use App\Message\GeneratePdfMessage;
use App\Repository\ArticleRepository;
use Dompdf\Dompdf;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GeneratePdfMessageHandler
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private string $projectDir
    ) {}

    public function __invoke(GeneratePdfMessage $message)
    {
        $article = $this->articleRepository->find($message->getArticleId());
        if (!$article) {
            throw new \Exception('Article not found');
        }

        $dompdf = new Dompdf();
        
        $html = sprintf(
            '<h1>%s</h1><div>Author: %s</div><div>%s</div>',
            $article->getTitle(),
            $article->getAuthor()?->getEmail(),
            $article->getContent()
        );
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfDir = $this->projectDir . '/public/pdfs';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }

        file_put_contents(
            sprintf('%s/article_%d.pdf', $pdfDir, $message->getArticleId()),
            $dompdf->output()
        );
    }
}