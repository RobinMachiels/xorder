<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\XlsxExporter;

/**
 * Generates and streams a local .xlsx backup file to the merchant's computer.
 */
final class ExportController extends BaseController
{
    private OrderRepository $orders;
    private UserRepository $users;
    private XlsxExporter $exporter;

    public function __construct(
        App $app,
        AuthService $auth,
        OrderRepository $orders,
        UserRepository $users,
        XlsxExporter $exporter
    ) {
        parent::__construct($app, $auth);
        $this->orders = $orders;
        $this->users = $users;
        $this->exporter = $exporter;
    }

    public function exportXlsx(Request $request): Response
    {
        $user = $this->requireUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $orders = $this->orders->getAllWithItemsForExport($user->id);
        $kitchen = $this->orders->getKitchenSummary($user->id);

        $binary = $this->exporter->generate($user, $orders, $kitchen);
        $this->users->markBackupDone($user->id);

        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $user->businessName));
        $slug = trim($slug, '-') ?: 'zaak';
        $filename = 'xorder-backup-' . $slug . '-' . date('Y-m-d_Hi') . '.xlsx';

        return Response::download(
            $binary,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}
