<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyApi
{
    private $accessToken;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private HttpClientInterface $httpClient
    ) {}

    private function getAccessToken(): string
    {
        if (!$this->accessToken) {
            $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
        }

        return $this->accessToken;
    }

    public function search(string $query): array
    {
        $response = $this->httpClient->request('GET', 'https://api.spotify.com/v1/search', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ],
            'query' => [
                'q' => $query,
                'type' => 'artist',
                'limit' => 9,
            ],
        ]);

        return $response->toArray();
    }

    public function getArtist(string $id): array
    {
        $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/artists/{$id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ],
        ]);

        return $response->toArray();
    }

    public function getArtistTopTracks(string $id, array $params = []): array
    {
        $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/artists/{$id}/top-tracks", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ],
            'query' => $params,
        ]);

        return $response->toArray();
    }
}