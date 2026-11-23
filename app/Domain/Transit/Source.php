<?php

namespace App\Domain\Transit;

interface Source
{
    public function pull(?string $tag): ?Feed;
}
