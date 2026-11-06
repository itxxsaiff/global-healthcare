<?php

namespace App\Enums;

enum ArticleType: string
{
    case Standard = 'standard';
    case Guide = 'guide';

    public function label(): string
    {
        return $this === self::Standard ? 'Standard health article' : 'Detailed guide';
    }

    /** Word-count range from the client document. */
    public function wordRange(): array
    {
        return $this === self::Standard ? [600, 1000] : [1200, 1500];
    }
}
