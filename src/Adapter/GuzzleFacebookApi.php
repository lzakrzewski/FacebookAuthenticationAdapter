<?php

namespace Lucaszz\FacebookAuthenticationAdapter\Adapter;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleFacebookApi implements FacebookApi
{
    /** @var ClientInterface */
    private $client;
    /** @var string */
    private $redirectUri;
    /** @var int */
    private $appId;
    /** @var string */
    private $appSecret;

    private $logger;

    /**
     * @param ClientInterface      $client
     * @param string               $redirectUri
     * @param int                  $appId
     * @param string               $appSecret
     * @param                      $logger
     */
    public function __construct(ClientInterface $client, $redirectUri, $appId, $appSecret, $logger = null)
    {
        if ($logger !== null && !is_subclass_of($logger, 'Psr\Log\LoggerInterface')) {
            throw new \InvalidArgumentException('Logger must implement Psr\Log\LoggerInterface.');
        }

        $this->client = $client;
        $this->redirectUri = $redirectUri;
        $this->logger = $logger;
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    /** {@inheritdoc} */
    public function dialog()
    {
        $url = sprintf(FacebookApi::OAUTH_DIALOG_URL.'?client_id=%d&redirect_uri=%s', $this->appId, urlencode($this->redirectUri));
        header("Location: {$url}");
        exit;
    }

    /** {@inheritdoc} */
    public function accessToken($code)
    {
        $request = $this->accessTokenRequest($code);

        try {
            $response = $this->client->send($request);
            $accessToken = $this->accessTokenFromResponse($response);

            if (null === $accessToken) {
                throw $this->facebookApiException('Unable to get access token from response.', $request, $response);
            }

            return $accessToken;
        } catch (RequestException $e) {
            throw $this->facebookApiException('An error with facebook graph api occurred: ', $request, null, $e);
        }
    }

    /** {@inheritdoc} */
    public function me($accessToken, array $fields = array())
    {
        $request = $this->meRequest($accessToken, $fields);

        try {
            $response = $this->client->send($request);
        } catch (RequestException $e) {
            throw $this->facebookApiException('An error with facebook graph api occurred: ', $request, null, $e);
        }

        try {
            $fields = json_decode($response->getBody(), true);
        } catch (\RuntimeException $e) {
            throw $this->facebookApiException(sprintf('Facebook graph api response body is not in JSON format: %s given.', $response->getBody()), $request, $response);
        }

        return $fields;
    }

    private function accessTokenRequest($code)
    {
        $request = new Request('GET', FacebookApi::GRAPH_API_ACCESS_TOKEN_URL);
        $query = $request->getUri();

        $query = Uri::withQueryValue($query, 'client_id', $this->appId);
        $query = Uri::withQueryValue($query, 'redirect_uri', urlencode($this->redirectUri));
        $query = Uri::withQueryValue($query, 'client_secret', $this->appSecret);
        $query = Uri::withQueryValue($query, 'code', $code);

        return $request->withUri($query);
    }

    private function meRequest($accessToken, array $fields)
    {
        $request = new Request('GET', FacebookApi::GRAPH_API_ME_URL);
        $query = $request->getUri();

        $query = Uri::withQueryValue($query, 'access_token', $accessToken);
        if (!empty($fields)) {
            $query = Uri::withQueryValue($query, 'fields', implode(',', $fields));
        }

        return $request->withUri($query);
    }

    private function accessTokenFromResponse(ResponseInterface $response)
    {
        try {
            $data = json_decode($response->getBody(), true);
        } catch (\RuntimeException $e) {
            return ;
        }

        if (isset($data['access_token'])) {
            return $data['access_token'];
        }
    }

    private function facebookApiException($message, RequestInterface $request, ResponseInterface $response = null, RequestException $exception = null)
    {
        if (null !== $exception) {
            $message .= $exception->getMessage();
        }

        if (null !== $this->logger) {
            $context = array(
                'request' => $request,
            );

            if (null !== $response) {
                $context['response'] = (string) $response;
            }

            $this->logger->error($message, $context);
        }

        return new FacebookApiException($message);
    }
}
