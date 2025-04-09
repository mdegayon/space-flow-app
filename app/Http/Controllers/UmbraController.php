<?php

namespace App\Http\Controllers;

use App\Services\UmbraS3Service;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Storage;


class UmbraController extends Controller
{
    protected UmbraS3Service $umbraService;

    public function __construct(UmbraS3Service $umbraService)
    {
        $this->umbraService = $umbraService;
    }

    /**
     * Endpoint para obtener la lista de archivos en S3.
     */
    public function listFiles(): JsonResponse
    {
        $files = $this->umbraService->listFiles();
        return response()->json($files);
    }

    /**
     * Endpoint para obtener la lista de carpetas en S3.
     */
    public function listFolders(): JsonResponse
    {
        $folders = $this->umbraService->listFolders();
        return response()->json($folders);
    }


    public function showFolderContents($folder)
    {
        $contents = $this->umbraService->showFolderContents($folder);

        if (isset($contents['error'])) {
            return response()->json(['error' => $contents['error']], 500);
        }

        return response()->json($contents);
    }

    public function listFolderContents(string $folder): JsonResponse
    {
        $contents = $this->umbraService->listFolderContents($folder);
        return response()->json($contents);
    }

    /**
     * Endpoint para obtener la lista de carpetas desde el sitio web del bucket.
     */
    public function listFoldersFromWeb(): JsonResponse
    {
        $folders = $this->umbraService->listFoldersFromWeb();
        return response()->json($folders);
    }
}
