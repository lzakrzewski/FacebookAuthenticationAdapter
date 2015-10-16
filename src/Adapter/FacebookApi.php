<?php

namespace Lucaszz\FacebookAuthenticationAdapter\Adapter;

interface FacebookApi
{
    const OAUTH_DIALOG_URL = 'https://www.facebook.com/v2.5/dialog/oauth';
    const GRAPH_API_ME_URL = 'https://graph.facebook.com/v2.5/me';
    const GRAPH_API_ACCESS_TOKEN_URL = 'https://graph.facebook.com/v2.5/oauth/access_token';

    /**
     * Redirect to facebook
     *
     * @return void
     */
    public function dialog();

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
