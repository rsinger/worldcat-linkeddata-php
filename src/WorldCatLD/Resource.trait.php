<?php
namespace WorldCatLD;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Promise;
use WorldCatLD\exceptions\ResourceNotFoundException;

/**
 * Class Resource
 * @package WorldCatLD
 */
trait Resource
{
    protected $httpClient;
    protected $baseUrl = 'http://www.worldcat.org/';

    /**
     * @return HttpClient
     */
    protected function getHttpClient()
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = new HttpClient();
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
    protected function fetchResources(array $ids)
    {
        $client = $this->getHttpClient();
        $promises = [];
        foreach ($ids as $id) {
            $promises[$id] = $client->getAsync($id . '.jsonld', ['Accept' => 'application/ld+json']);
        }
        return Promise\settle($promises)->wait();
    }


}