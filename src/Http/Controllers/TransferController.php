<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\CancelTransferRequest;
use JayI\Roster\Http\Requests\ConfirmImportRequest;
use JayI\Roster\Http\Requests\DownloadTransferRequest;
use JayI\Roster\Http\Requests\IndexTransfersRequest;
use JayI\Roster\Http\Requests\ShowImportTemplateRequest;
use JayI\Roster\Http\Requests\ShowTransferRequest;
use JayI\Roster\Http\Requests\StartExportRequest;
use JayI\Roster\Http\Requests\StartImportRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TransferController
{
    public function index(IndexTransfersRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function import(StartImportRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function export(StartExportRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowTransferRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function confirm(ConfirmImportRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(CancelTransferRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function template(ShowImportTemplateRequest $request): Response
    {
        return $request->persist();
    }

    public function download(DownloadTransferRequest $request): StreamedResponse
    {
        return $request->persist();
    }
}
