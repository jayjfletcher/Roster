<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\CancelTransferRequest;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\ConfirmImportRequest;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\DownloadTransferRequest;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\IndexTransfersRequest;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\ShowImportTemplateRequest;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\ShowTransferRequest;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\StartExportRequest;
use RefactorCircus\Roster\Domains\Transfer\Http\Requests\StartImportRequest;
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
