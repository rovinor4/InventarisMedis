<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Repositories\InventoryRepository;

abstract class Controller
{
    public function __construct(
        protected InventoryRepository $repository,
        protected Auth $auth,
        protected Logger $logger
    ) {
    }

    protected function userId(): ?int
    {
        $user = $this->auth->user();

        return $user === null ? null : (int) $user['id'];
    }
}
