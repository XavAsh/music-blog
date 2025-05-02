<?php

namespace App\MessageHandler;

use App\Message\GenerateSpotifyPdfMessage;
use Dompdf\Dompdf;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class GenerateSpotifyPdfMessageHandler
{
    public function __construct(
        private string $projectDir,
        private ?LoggerInterface $logger = null
    ) {}

    public function __invoke(GenerateSpotifyPdfMessage $message)
    {
        try {
            $artist = $message->getArtistData();
            $dompdf = new Dompdf();
            
            // Get image URL safely
            $imageUrl = !empty($artist['images'][0]['url']) ? $artist['images'][0]['url'] : '';
            $genres = isset($artist['genres']) ? $this->formatGenres($artist['genres']) : 'No genres listed';
            $followers = isset($artist['followers']['total']) ? number_format($artist['followers']['total']) : '0';
            
            $html = <<<HTML
            <!DOCTYPE html>
            <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #1DB954; }
                        .metadata { margin: 20px 0; }
                        img { max-width: 300px; }
                        .genres { color: #666; }
                        .spotify-link { color: #1DB954; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <h1>{$artist['name']}</h1>
                    <div class="metadata">
                        <p>Followers: {$followers}</p>
                        <p>Popularity: {$artist['popularity']}/100</p>
                        <p class="genres">Genres: {$genres}</p>
                    </div>
                    <img src="{$imageUrl}" alt="{$artist['name']}">
                    <p><a class="spotify-link" href="{$artist['external_urls']['spotify']}">View on Spotify</a></p>
                </body>
            </html>
HTML;
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfDir = $this->projectDir . '/public/pdfs';
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }

            file_put_contents(
                sprintf('%s/spotify_artist_%s.pdf', $pdfDir, $message->getArtistId()),
                $dompdf->output()
            );

            $this->logger?->info('Spotify artist PDF generated', [
                'artist_id' => $message->getArtistId()
            ]);
        } catch (\Exception $e) {
            $this->logger?->error('Spotify PDF generation failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function formatGenres(array $genres): string
    {
        return empty($genres) ? 'No genres listed' : implode(', ', $genres);
    }
}