<?php

namespace App\Message;

class GeneratePdfMessage
{
    public function __construct(
        private int $articleId
    ) {}

    public function getArticleId(): int
    {
        return $this->articleId;
    }
}