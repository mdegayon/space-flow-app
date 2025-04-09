<?php

namespace App\Services;

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class UmbraS3Service
{
    protected S3Client $s3Client;
    protected Client $httpClient;
    protected string $bucket = 'umbra-open-data-catalog';
    protected string $region = 'us-west-2';
    protected string $websiteUrl = 'http://umbra-open-data-catalog.s3-website.us-west-2.amazonaws.com/?prefix=';

    public function __construct()
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $this->region,
            'credentials' => false, // No autenticación
        ]);

        $this->httpClient = new Client();
    }

    /**
     * Lista los archivos dentro del bucket S3.
     */
    public function listFiles(): array
    {
        $result = $this->s3Client->listObjectsV2([
            'Bucket' => $this->bucket,
        ]);

        $files = $result['Contents'] ?? [];

        return array_map(function ($file) {
            return [
                'name' => basename($file['Key']),
                'path' => $file['Key'],
                'size' => $file['Size'],
                'last_modified' => $file['LastModified'],
                'url' => $this->getFileUrl($file['Key']),
            ];
        }, $files);
    }

    /**
     * Lista las carpetas principales del bucket S3.
     */
    public function listFolders(): array
    {
        $result = $this->s3Client->listObjectsV2([
            'Bucket'    => $this->bucket,
            'Delimiter' => '/', // Agrupar por "carpetas"
        ]);

        $folders = $result['CommonPrefixes'] ?? [];
        return array_map(fn ($folder) => $folder['Prefix'], $folders);
    }

    public function showFolderContents($folder)
    {
        $folders = [];
        $files = [];

        try {
            $result = $this->s3Client->listObjectsV2([
                'Bucket'    => $this->bucket,
                'Prefix'    => rtrim($folder, '/') . '/',
                'Delimiter' => '/',
            ]);

            // Obtener carpetas
            if (isset($result['CommonPrefixes'])) {
                foreach ($result['CommonPrefixes'] as $prefix) {
                    $folders[] = $prefix['Prefix'];
                }
            }

            // Obtener archivos
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $content) {
                    // Excluir el propio prefijo de la lista de archivos
                    if ($content['Key'] !== rtrim($folder, '/') . '/') {
                        $files[] = $content['Key'];
                    }
                }
            }
        } catch (AwsException $e) {
            // Manejo de errores
            return [
                'error' => $e->getMessage(),
            ];
        }

        return [
            'folders' => $folders,
            'files'   => $files,
        ];
    }

    public function listFolderContentsNew(string $folder): array
    {
        $folder = rtrim($folder, '/') . '/'; // Asegurar que el prefijo termina en "/"

        $result = $this->s3Client->listObjectsV2([
            'Bucket' => $this->bucket,
            'Prefix' => $folder,  // Especificamos la carpeta a listar
            //'Delimiter' => '/',  // IMPORTANTE: NO USAR Delimiter para ver archivos
        ]);

        // Obtener carpetas dentro de la carpeta actual
        $folders = [];
        $files = [];

        if (!empty($result['Contents'])) {
            foreach ($result['Contents'] as $content) {
                $key = $content['Key'];

                // Si termina en "/", es una carpeta
                if (str_ends_with($key, '/')) {
                    $folders[] = $key;
                } else {
                    $files[] = [
                        'name' => basename($key),
                        'path' => $key,
                        'size' => $content['Size'],
                        'last_modified' => $content['LastModified'],
                        'url' => $this->getFileUrl($key),
                    ];
                }
            }
        }

        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }


    public function listFolderContents(string $folder): array
    {
        $result = $this->s3Client->listObjectsV2([
            'Bucket'    => $this->bucket,
            'Prefix'    => $folder,   // Indica el directorio a listar
            'Delimiter' => '/',       // Para agrupar en carpetas
        ]);

        // Obtener carpetas dentro de la carpeta actual
        $folders = array_map(fn ($f) => $f['Prefix'], $result['CommonPrefixes'] ?? []);

        // Obtener archivos dentro de la carpeta actual
        $files = array_map(function ($file) {
            return [
                'name' => basename($file['Key']),
                'path' => $file['Key'],
                'size' => $file['Size'],
                'last_modified' => $file['LastModified'],
                'url' => $this->getFileUrl($file['Key']),
            ];
        }, $result['Contents'] ?? []);

        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }


    /**
     * Genera la URL pública de un archivo en S3.
     */
    public function getFileUrl(string $key): string
    {
        $key = ltrim($key, '/');
        $key = str_replace(' ', '%20', $key);
        return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$key}";
    }

    /**
     * Lista las carpetas desde la versión Web del bucket (Scraping).
     */
    public function listFoldersFromWeb(): array
    {
        $response = $this->httpClient->get($this->websiteUrl);
        $html = (string) $response->getBody();

        $crawler = new Crawler($html);
        $folders = [];

        $crawler->filter('pre a')->each(function (Crawler $node) use (&$folders) {
            $text = trim($node->text());
            if (str_ends_with($text, '/')) {
                $folders[] = $text;
            }
        });

        return $folders;
    }
}
