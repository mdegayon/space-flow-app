<?php

namespace App\Services;

    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\GuzzleException;
    use GuzzleHttp\Exception\RequestException;

class ApiService
{
    private $client;
    private $token;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://sh.dataspace.copernicus.eu/api/v1/',
            'timeout' => 10.0,
        ]);

        $this->authenticate();
    }

    public function getMessage()
    {
        return 'Bienvenido desde ApiService';
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    private function authenticate(): void
    {
        $apiClientId = 'sh-38432bc7-86af-4ab7-b5a5-20e0f06fae7b';
        $apiClientSecret = 'QoWVQF5KqUV4BBrt1zTC1Tjgc9oA54T0';

        try {
            $response = $this->client->post('oauth/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $apiClientId,
                    //'client_id' => env('API_CLIENT_ID'),
                    'client_secret' => $apiClientSecret,
                    //'client_secret' => env('API_CLIENT_SECRET'),
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $this->token = $data['access_token'];
        } catch (RequestException $e) {
            throw new \Exception('Error al autenticar con la API: ' . $e->getMessage());
        }
    }

    public function getStatistics()
    {
        $evalscript = <<<EOT
        //VERSION=3
        function setup() {
          return {
            input: [{ bands: ["B04", "dataMask"] }],
            output: [
              { id: "output_B04", bands: 1, sampleType: "FLOAT32" },
              { id: "dataMask", bands: 1 }
            ]
          }
        }
        function evaluatePixel(samples) {
          return {
            output_B04: [samples.B04],
            dataMask: [samples.dataMask]
          }
        }
        EOT;

        $statsRequest = [
            "input" => [
                "bounds" => [
                    "bbox" => [414315, 4958219, 414859, 4958819],
                    "properties" => ["crs" => "http://www.opengis.net/def/crs/EPSG/0/32633"]
                ],
                "data" => [
                    [
                        "type" => "sentinel-2-l2a",
                        "dataFilter" => ["mosaickingOrder" => "leastRecent"],
                    ]
                ]
            ],
            "aggregation" => [
                "timeRange" => ["from" => "2020-07-04T00:00:00Z", "to" => "2020-07-05T00:00:00Z"],
                "aggregationInterval" => ["of" => "P1D"],
                "evalscript" => $evalscript,
                "resx" => 10,
                "resy" => 10
            ]
        ];

        try {
            $response = $this->client->post('statistics', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $statsRequest,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception('Error al obtener estadísticas: ' . $e->getMessage());
        }
    }

}
