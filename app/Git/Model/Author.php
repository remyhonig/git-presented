<?php

declare(strict_types=1);

namespace App\Git\Model;

readonly class Author
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    public static function fromGitFormat(string $authorString): self
    {
        // Format: "Name <email>"
        if (preg_match('/^(.+?)\s*<(.+?)>$/', $authorString, $matches)) {
            return new self(trim($matches[1]), trim($matches[2]));
        }

        return new self($authorString, '');
    }

    public function getGravatarUrl(int $size = 40): string
    {
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
    }
}
