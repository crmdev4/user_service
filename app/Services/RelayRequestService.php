<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
//use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Repositories\EndpointRegisterRepository;
use App\Repositories\EndpointRelationRepository;
use App\Services\BaseService;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;

class RelayRequestService extends BaseService
{

    public function __construct(
        protected Client $client,
        protected ResponseFactory $responseFactory,
        EndpointRegisterRepository $repo,
        EndpointRelationRepository $repoRelation
    ) {
        $this->repo = $repo;
        $this->repoRelation = $repoRelation;
    }

    public function relay(Request $request): Response
    {
        $fullpath = $request->path();
        $segments = explode('/', $fullpath);
        $service = $segments[2] ?? null;
        $path = $this->replaceUuidWithId($fullpath);
        $path = $this->createRealRelayPath($path, $service);
        $cacheKey = md5($request->getContent());
        $queryParams = $request->query();

        if (!empty($queryParams)) {
            $queryParamsString = http_build_query($queryParams); // Convert query parameters to string
            $cacheKey .= '/' . $queryParamsString; // Append query params to the cache key
        }
        $cacheKey = $fullpath . '/' . $cacheKey;

        if (in_array($request->method(), ['PATCH', 'DELETE', 'POST', 'PUT'])) {
            Cache::forget($cacheKey);
        }

        $relay = $this->getRelay($cacheKey, $path, $service, $request);

        if (($relay == null) or (empty($relay))) {
            $response['status'] = 404;
            $response['data'] = (object) ['message' => 'Ambiguous mappings router detected, no routing found for current path.'];

            //$this->logRelay($response, $request->method() . ' ' . $service, $request->path());
            return $this->sendErrorRelay($response);
        }

        if ($request->isMethod('GET')) {
            $response = Cache::remember($cacheKey, 1, function () use ($request, $service, $relay) {
                $get = $this->sendRelayRequest($request, $relay);
                if ($get == null) {
                    return null;
                }

                $response['header'] = $get->getHeader('Content-Type');
                $response['status'] = $get->getStatusCode();
                $response['data'] = json_decode($get->getBody()->getContents());

                $relayPath = $relay->base_uri . '/' . $this->createRealRelayPath($request->path(), $relay->service_name);
                //$this->logRelay($response, $request->method() . ' ' . $service, $relayPath);
                return $response;
            });
        } else {
            // Directly fetch data without caching for non-GET requests
            $get = $this->sendRelayRequest($request, $relay);
            if ($get == null) {
                $response = null;
            } else {

                // Set response data just like in the cached response
                $response['header'] = $get->getHeader('Content-Type');
                $response['status'] = $get->getStatusCode();
                $response['data'] = json_decode($get->getBody()->getContents());

                $relayPath = $relay->base_uri . '/' . $this->createRealRelayPath($request->path(), $relay->service_name);
                //$this->logRelay($response, $request->method() . ' ' . $service, $relayPath);
            }
        }
        ;

        if ($response == null) {
            $response['status'] = 503;
            $response['data'] = (object) ['message' => '503 - Service Unavailable'];
            Cache::forget($cacheKey);
            //$this->logRelay($response, $request->method() . ' ' . $service, $relay->base_uri . '/' . $this->createRealRelayPath($request->path(), $relay->service_name));
            return $this->sendErrorRelay($response);
        }
        // dd($response);
        //\Log::info("request URL : " . $fullpath);
        //\Log::info("request method : " . $request->method());
        //\Log::info("request body : ", [$request->getContent()]);
        //\Log::info("request result : ", [$response]);

        if (empty($response['data']->data)) {
            $responseCode = (int) $response['status'];
            if ($responseCode === 422) {
                if (isset($response['data']->error)) {
                    $errorDetails = $response['data']->error;

                    $errorMessage = $this->formatValidationError($errorDetails);

                    $response['status'] = $response['status'] ?? false;
                    $response['data'] = (object) ['message' => 'Validations Error', 'code' => '422', 'error' => $errorMessage];
                } else {
                    $response['status'] = $response['status'] ?? false;
                    $response['data'] = (object) [
                        'message' => $response['data']->message,
                        'success' => false,
                        'error' => 'Validation Error'
                    ];
                }
                Cache::forget($cacheKey);
                return $this->sendErrorRelay($response);
            }

            if ($responseCode === 500) {
                $response['success'] = $response['data']->success ?? true;
                $response['message'] = $response['data']->error ?? $response['data']->message;
                $response['status'] = $response['status'];
                Cache::forget($cacheKey);
                return $this->sendErrorRelay($response);
            }

            $response['status'] = $response['status'];
            $response['data'] = (object) ['message' => 'Data not found'];
            Cache::forget($cacheKey);
            return $this->sendErrorRelay($response);
        }


        $data = $response['data'];
        $getRelation = $this->repoRelation->get(['endpoint_register_id' => $relay->id]);
        $relayRelation = [];
        if (!empty($getRelation)) {
            foreach ($getRelation as $val) {

                $relayRelation[$val->relation_references_name] = $this->repo->find($val->relation_endpoint_register_id);
            }

            if (isset($data->data)) {

                $res = [];
                $cleanData = [];
                if (is_array($data->data) && isset($data->data[0]) && is_object($data->data[0])) {
                    $res['success'] = $data->success ?? false ?: true;
                    $res['message'] = $data->message ?? false ?: 'No massage';
                    foreach ($data->data as $hasKey => $hasValue) {

                        $cleanData[$hasKey] = $this->processDataItem($request, $relayRelation, $hasValue);
                    }
                    $res['data'] = $cleanData;
                    $res['draw'] = $data->draw ?? false ?: [];
                    $res['recordsTotal'] = $data->recordsTotal ?? false ?: [];
                    $res['recordsFiltered'] = $data->recordsFiltered ?? false ?: [];
                    $res['meta'] = $data->meta ?? false ?: [];
                    $res['totalAmount'] = $data->totalAmount ?? false ?: [];
                    $res['userData'] = $data->userData ?? false ?: [];
                } elseif (is_object($data->data)) {
                    $res['success'] = $data->success ?? false ?: true;
                    $res['message'] = $data->message ?? false ?: 'No massage';
                    $cleanData = $this->processDataItem($request, $relayRelation, $data->data);

                    $res['data'] = $cleanData;
                } else {
                    $res = $data;
                }
                $data = $res;
            }
        }

        //$data = ($data->data === [0]) ? ['success' => $data->success, 'message' => $data->message, 'data' => null] : json_encode($res);
        $data = json_encode($data);

        return $this->responseFactory->make(
            content: $data,
            status: $response['status'],
            headers: ['Content-Type' => $response['header']]
        );
    }

    private function formatValidationError($errorDetails)
    {
        $errorMessage = 'Validation error';

        if (is_string($errorDetails)) {
            $errorDetailsArray = explode(', ', $errorDetails);
            $formattedErrors = array_map(function ($error) {
                return $error;
            }, $errorDetailsArray);

            $errorMessage = implode(', ', $formattedErrors);
        }

        return $errorMessage;
    }

    private function processDataItem($request, $relayRelation, $dataItem)
    {
        $dataRelation = [];
        $cleanData = [];

        if (isset($relayRelation)) {
            foreach ($relayRelation as $key => $val) {
                $cacheKey = md5($dataItem->$key);
                $responseRelasi = [];
                if (Cache::has($cacheKey)) {
                    $responseRelasi = Cache::get($cacheKey);
                    //\Log::info("Cache hit for key: $cacheKey");
                } else {
                    $get = $this->sendRelayRequestRelation($request, $val, $dataItem->$key);

                    if (!empty($get)) {
                        $responseRelasi = json_decode($get->getBody()->getContents());

                        Cache::put($cacheKey, $responseRelasi, 5);
                    }
                    //\Log::info("Cache miss for key: $cacheKey. Data cached.");
                }

                //$responseRelasi = $this->sendRelayRequestRelation($request, $val, $dataItem->$key);

                // if ($responseRelasi != null) {
                //     $getRelation = $responseRelasi;
                //     $dataRelation[$key] = (object)$getRelation->data;
                // }
                if ($responseRelasi !== null) {
                    if (
                        is_object($responseRelasi) &&
                        ((property_exists($responseRelasi, 'code') && $responseRelasi->code === 404) ||
                            (property_exists($responseRelasi, 'code') && $responseRelasi->code === 422))
                    ) {
                        $dataRelation[$key] = null;
                    } else {
                        $getRelation = $responseRelasi;
                        if (is_object($getRelation) && isset($getRelation->data)) {
                            $dataRelation[$key] = (object) $getRelation->data;
                        }
                    }
                }

            }
        }

        foreach ($dataItem as $hasKey => $hasValue) {
            $cleanData[$hasKey] = $hasValue;
            foreach ($dataRelation as $keys => $relation) {
                if ($hasKey == $keys) {
                    $cleanData[$keys . '_rel'] = $relation;
                }
            }
        }


        return $cleanData;
    }

    private function sendRelayRequest($request, $relay)
    {
        $service = $relay->service_name;
        $path = $this->createRealRelayPath($request->path(), $service);
        $relayPath = $relay->base_uri . '/' . $path;
        $apiKey = $relay->api_key;

        $options = $this->prepareOptions($request, $apiKey);

        $response = null;
        try {
            $response = $this->client->request(
                $request->method(),
                $relayPath,
                $options
            );
        } catch (ConnectException $e) {
            return null;
        }

        return $response;
    }

    private function sendRelayRequestRelation($request, $relay, $id)
    {
        $service = $relay->service_name;
        $path = $this->createRealRelayPath($relay->path, $service);
        $relayPath = $relay->base_uri . '/' . $path . '/' . $id;
        $apiKey = $relay->api_key;
        $relayPath = $this->removePlaceholderFromUrl($relayPath);

        $options = $this->prepareOptions($request, $apiKey);

        $response = null;
        try {

            $response = $this->client->request(
                $relay->method,
                $relayPath,
                $options
            );
        } catch (ConnectException $e) {
            return null;
        }

        return $response;
    }

    private function prepareOptions($request, $apiKey)
    {
        $options = [
            'verify' => false,
            'http_errors' => false,
        ];

        if ($request->hasHeader('Accept')) {
            $options['headers']['Accept'] = $request->header('Accept');
        }

        if ($request->hasHeader('Content-Type')) {
            $options['headers']['Content-Type'] = $request->header('Content-Type');
        }

        if ($apiKey) {
            $options['headers']['Authorization'] = 'Bearer ' . $apiKey;
        }

        $query = $request->getQueryString();
        if ($query) {
            $options['query'] = $query;
        }

        $content = $request->getContent();
        if ($content) {
            $options['body'] = $content;
        }

        // Handle form data
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            // Initialize the options array
            
            // Check if the request contains file uploads
            if ($request->hasFile('attachment')) {
                // Handle single or multiple file uploads under the 'attachment' field
                $files = $request->file('attachment');
                $options = [];
                // If it's a single file (not an array)
                if (is_array($files)) {
                    
                    // Handle multiple files as an array
                    $attachments = [];
                    foreach ($files as $file) {
                        $attachments[] = [
                            'attachment' => base64_encode(file_get_contents($file->getPathname())), // Base64 encoding the file content
                            'filename' => $file->getClientOriginalName(), // Store the original filename
                        ];
                    }
                    // Add the array of file data to form params
                    $options['form_params']['attachments'] = $attachments;
                } else {
                    // Handle a single file (base64 encoding)
                    $options['form_params']['attachment'] = [
                        'attachment' => base64_encode(file_get_contents($files->getPathname())), // Base64 encoding the file content
                        'filename' => $files->getClientOriginalName(), // Store the original filename
                    ];
                }
            }
        }


        return $options;
    }

    private function createRealRelayPath($uri, $service)
    {
        $segments = explode('/', $uri);
        $index = array_search($service, $segments);

        if ($index !== false) {
            unset($segments[$index]);
        }

        $newUri = implode('/', $segments);
        return $newUri;
    }

    private function replaceUuidWithId($uri)
    {
        // Regex pattern to match UUID
        $uuidPattern = '/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/';
        $newUri = preg_replace($uuidPattern, '{id}', $uri);

        return $newUri;
    }

    private function removePlaceholderFromUrl($url)
    {
        $url = preg_replace("/\{id\}\//", '', $url);
        $url = preg_replace('#(?<!:)//+#', '/', $url);
        return $url;
    }

    private function getRelay($cacheKey, $path, $service, $request)
    {
        $method = $request->method();

        if (in_array($request->method(), ['PATCH', 'DELETE', 'POST'])) {
            Cache::forget($cacheKey);
        }
        $cacheKey = 'relay_' . $cacheKey;
        $relay = Cache::remember($cacheKey, 30, function () use ($path, $service, $method) {
            return $this->repo->findWhere([
                'path' => $path,
                'service_name' => $service,
                'method' => $method,
                'status' => '1'
            ]);
        });

        return $relay;
    }

    private function sendErrorRelay($response)
    {
        // dd($response);
        return $this->responseFactory->make(
            content: [
                'message' => $response['data']->message ?? $response['message'],
                'error' => $response['data']->error ?? false,
                // 'code' => $response['data']->code ?? $response['status'],
            ],
            status: $response['status'],
            headers: ['Content-Type' => 'application/json']
        );
    }

    private function logRelay($data, $service, $log)
    {
        return activity($service)
            ->withProperties(['data' => $data])
            ->performedOn(Auth::user())
            ->log($log);
    }

    /*     private function getKey($service){
        $mappings   = new Collection(config('mappings'));
        $filteredMappings = $mappings->filter(function ($value, $key) use ($service) {
            return ($service== $key);
        });
        return $filteredMappings;
    }
 */
}
