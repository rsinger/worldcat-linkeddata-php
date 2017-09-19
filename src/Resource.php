<?php
namespace WorldCatLD;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Promise;
use WorldCatLD\exceptions\ResourceNotFoundException;
use GuzzleHttp\Pool;

/**
 * Class Resource
 * @package WorldCatLD
 */
trait Resource
{
    protected $httpClient;
    protected $baseUrl = 'http://www.worldcat.org/';

    static public $async = true;

    static protected $userAgent = 'worldcat-linkeddata-php/0.1';

    /**
     * @return HttpClient
     */
    protected function getHttpClient()
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = new HttpClient(
                ['headers' => ['User-Agent' =>  self::$userAgent]]
            );
        }
        return $this->httpClient;
    }

    /**
     * @param string|null $url
     * @throws exceptions\ResourceNotFoundException
     */
    protected function fetchResourceData($url = null)
    {
        if (!$url) {
            $url = $this->getId();
        }
        $url .= '.jsonld';

        $response = $this->getHttpClient()->get($url, ['Accept' => 'application/json']);
        if ($response->getStatusCode() === 200) {
            $this->setSourceData(json_decode($response->getBody(), true));
        } else {
            throw new ResourceNotFoundException();
        }
    }

    /**
     * @param string $url
     * @return array
     * @throws exceptions\ResourceNotFoundException
     */
    protected function getRedirectLocation($url)
    {
        $redirectRequest = $this->getHttpClient()->head($url, ['allow_redirects' => false]);
        if ($redirectRequest->getStatusCode() === 303) {
            $location = $redirectRequest->getHeader('Location');
            return $location;
        } elseif ($redirectRequest->getStatusCode() === 301) {
            $location = $redirectRequest->getHeader('Location');
            return $this->getRedirectLocation($location[0]);
        }
        throw new ResourceNotFoundException();
    }

    /**
     * @param array $ids
     * @return array
     */
    private function fetchConcurrentResources(array $ids)
    {
        $client = $this->getHttpClient();

        $requests = function () use ($client, $ids) {
            foreach ($ids as $id) {
                yield function () use ($client, $id) {
                    return $client->getAsync($id . '.jsonld', ['Accept' => 'application/ld+json']);
                };
            }
        };

        $results = [];

        $pool = new Pool(
            $client,
            $requests(),
            [
                'concurrency' => 5,
                'fulfilled' => function ($response, $index) use ($ids, &$results) {
                    $results[$ids[$index]] = ['state' => 'fulfilled', 'value' => $response];
                }
            ]
        );
        $pool->promise()->wait(true);
        return $results;
    }

    /**
     * @param array $ids
     * @return array
     */
    private function fetchSequentialResources(array $ids)
    {
        $client = $this->getHttpClient();
        $results = [];
        foreach ($ids as $id) {
            $results[$id] = [
                'state' => 'fulfilled',
                'value' => $client->get($id . '.jsonld', ['Accept' => 'application/ld+json'])
            ];
        }
        return $results;
    }

    /**
     * Returns an array of responses keyed by id
     *
     * @param array $ids
     * @return array
     */
    protected function fetchResources(array $ids)
    {
        return self::$async ? $this->fetchConcurrentResources($ids) : $this->fetchSequentialResources($ids);
    }
}
