<?php

namespace Lzakrzewski\FacebookAuthenticationAdapter\Adapter;

interface FacebookApi
{
    const GRAPH_API_ME_URL = 'https://graph.facebook.com/v2.5/me';
    const GRAPH_API_ACCESS_TOKEN_URL = 'https://graph.facebook.com/v2.5/oauth/access_token';

    /**
     * Returns access token during code exchange.
     *
     * @param $code
     *
     * @throws FacebookApiException
     *
     * @return string
     */
    public function accessToken($code);

    /**
     * Returns a single user node as array.
     *
     * @param string $accessToken
     * @param array  $fields
     *
     * @throws FacebookApiException
     *
     * @return array
     */
    public function me($accessToken, array $fields = array());
}
