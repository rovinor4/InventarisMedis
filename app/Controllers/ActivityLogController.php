<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class ActivityLogController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        Response::success('Data berhasil ditampilkan', $this->repository->listActivityLogs());
    }
}
