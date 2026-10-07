<?php

namespace App\Contracts;

use App\Models\NewsSource;

interface NewsFetcher
{
    public function fetch(NewsSource $source): array;
}
