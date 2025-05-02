<?php

namespace App\Message;

class GenerateSpotifyPdfMessage
{
    public function __construct(
        private string $artistId,
        private array $artistData
    ) {}

    public function getArtistId(): string
    {
        return $this->artistId;
    }

    public function getArtistData(): array
    {
        return $this->artistData;
    }
}