<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\GenerateSpotifyPdfMessage;

#[Route('/api')]
class SpotifyController extends AbstractController
{
    private string $spotifyToken;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $spotifyClientId,
        private string $spotifyClientSecret
    ) {
        $this->spotifyToken = $this->getSpotifyToken();
    }

  
    
    #[Route('/spotify/search', name: 'api_spotify_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        if (!$query) {
            return $this->json(['error' => 'Query parameter is required'], 400);
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.spotify.com/v1/search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->spotifyToken
                ],
                'query' => [
                    'q' => $query,
                    'type' => 'artist',
                    'limit' => 10
                ]
            ]);

            return $this->json($response->toArray());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getSpotifyToken(): string
    {
        $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->spotifyClientId . ':' . $this->spotifyClientSecret)
            ],
            'body' => [
                'grant_type' => 'client_credentials'
            ]
        ]);

        $data = $response->toArray();
        return $data['access_token'];
    }

    #[Route('/spotify/artists/{id}/pdf', name: 'api_spotify_artist_pdf', methods: ['POST'])]
    public function generateArtistPdf(string $id, MessageBusInterface $messageBus): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/artists/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->spotifyToken
                ]
            ]);

            $artistData = $response->toArray();
            
            // Dispatch PDF generation message
            $messageBus->dispatch(new GenerateSpotifyPdfMessage($id, $artistData));

            return $this->json([
                'message' => 'PDF generation started',
                'pdf_url' => sprintf('/pdfs/spotify_artist_%s.pdf', $id)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }}