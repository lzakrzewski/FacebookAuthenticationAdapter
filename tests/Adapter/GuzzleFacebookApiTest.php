<?php

namespace Lucaszz\FacebookAuthenticationAdapter\Tests\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;
use Lucaszz\FacebookAuthenticationAdapter\Adapter\GuzzleFacebookApi;

class GuzzleFacebookApiTest extends \PHPUnit_Framework_TestCase
{
    /** @var ClientInterface */
    private $guzzleClient;
    /** @var History */
    private $history;
    /** @var Logger */
    private $logger;
    /** @var GuzzleFacebookApi */
    private $adapter;

    /** @test */
    public function it_requests_for_access_token_successfully()
    {
        $this->thereIsSuccessfullFacebookApiResponse();

        $accessToken = $this->adapter->accessToken('correct-code');

        $this->assertEquals('access-token', $accessToken);
        $this->assertThatRequestIsEqual($this->expectedSuccessfulAccessTokenRequest(), $this->history->getLastRequest());
    }

    /**
     * @test
     *
     * @expectedException \Lucaszz\FacebookAuthenticationAdapter\Adapter\FacebookApiException
     */
    public function it_fails_when_unable_to_parse_token_from_response_during_requesting_for_access_token()
    {
        $this->thereIsFacebookApiResponseWithWrongToken();

        $this->adapter->accessToken('correct-code');
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function it_can_cot_be_created_with_invalid_logger()
    {
        new GuzzleFacebookApi($this->guzzleClient, 'http://localhost/facebook/login', '1234', 'secret', new \stdClass());
    }

    /** @test */
    public function it_logs_when_unable_to_parse_token_from_response_during_requesting_for_access_token()
    {
        $this->thereIsFacebookApiResponseWithWrongToken();
        $this->guzzleFacebookApiHasLoggerEnabled();

        try {
            $this->adapter->accessToken('correct-code');
        } catch (\Exception $e) {
        }

        $this->assertThatLogWithMessageWasCreated('Unable to get access token from response');
    }

    /**
     * @test
     *
     * @expectedException \Lucaszz\FacebookAuthenticationAdapter\Adapter\FacebookApiException
     */
    public function it_fails_when_facebook_api_throws_an_exception_during_requesting_for_access_token()
    {
        $this->thereIsFacebookApiException();

        $this->adapter->accessToken('correct-code');
    }

    /** @test */
    public function it_logs_when_facebook_api_throws_an_exception_during_requesting_for_access_token()
    {
        $this->thereIsFacebookApiException();
        $this->guzzleFacebookApiHasLoggerEnabled();

        try {
            $this->adapter->accessToken('correct-code');
        } catch (\Exception $e) {
        }

        $this->assertThatLogWithMessageWasCreated('An error with facebook graph api occurred');
    }

    /**
     * @test
     *
     * @expectedException \Lucaszz\FacebookAuthenticationAdapter\Adapter\FacebookApiException
     */
    public function it_fails_when_facebook_api_returns_unsuccessful_response_during_requesting_for_access_token()
    {
        $this->thereIsFacebookApiUnsuccessfulResponse();

        $this->adapter->accessToken('correct-code');
    }

    /** @test */
    public function it_logs_when_facebook_api_returns_unsuccessful_response_during_requesting_for_access_token()
    {
        $this->thereIsFacebookApiUnsuccessfulResponse();
        $this->guzzleFacebookApiHasLoggerEnabled();

        try {
            $this->adapter->accessToken('correct-code');
        } catch (\Exception $e) {
        }

        $this->assertThatLogWithMessageWasCreated('An error with facebook graph api occurred');
    }

    /** @test */
    public function it_can_retrieve_user_node_successfully()
    {
        $requestedFields = array('first_name', 'last_name', 'gender', 'email', 'birthday', 'name');

        $userNode = $this->adapter->me($this->accessToken(), $requestedFields);

        $this->assertThatUserNodeContainsRequiredFields($requestedFields, $userNode);
        $this->assertThatUserNodeContainsId($userNode);
        $this->assertThatUserNodeContainsName($userNode);
    }

    /** @test */
    public function it_can_retrieve_empty_user_node_successfully()
    {
        $userNode = $this->adapter->me($this->accessToken(), array());

        $this->assertThatUserNodeContainsId($userNode);
        $this->assertThatUserNodeContainsName($userNode);
    }

    /**
     * @test
     *
     * @expectedException \Lucaszz\FacebookAuthenticationAdapter\Adapter\FacebookApiException
     */
    public function it_fails_when_unable_to_parse_json_response_during_retrieving_user_node()
    {
        $this->thereIsFacebookApiResponseWithWrongJson();

        $this->adapter->me($this->accessToken());
    }


    /** @test */
    public function it_logs_when_unable_to_parse_json_response_during_retrieving_user_node()
    {
        $this->thereIsFacebookApiResponseWithWrongJson();
        $this->guzzleFacebookApiHasLoggerEnabled();

        try {
            $this->adapter->me($this->accessToken());
        } catch (\Exception $e) {
        }

        $this->assertThatLogWithMessageWasCreated('Facebook graph api response body is not in JSON format');
    }

    /**
     * @test
     *
     * @expectedException \Lucaszz\FacebookAuthenticationAdapter\Adapter\FacebookApiException
     */
    public function it_fails_when_facebook_api_throws_an_exception_during_retrieving_me_fields()
    {
        $this->thereIsFacebookApiException();

        $this->adapter->me($this->accessToken());
    }

    /** @test */
    public function it_logs_when_facebook_api_throws_an_exception_during_retrieving_me_fields()
    {
        $this->thereIsFacebookApiException();
        $this->guzzleFacebookApiHasLoggerEnabled();

        try {
            $this->adapter->me($this->accessToken());
        } catch (\Exception $e) {
        }

        $this->assertThatLogWithMessageWasCreated('An error with facebook graph api occurred');
    }

    /** {@inheritdoc} */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = new Logger();
        $this->guzzleClient = new Client();

        $this->adapter = new GuzzleFacebookApi($this->guzzleClient, 'http://localhost/facebook/login', '1234', 'secret');
        $this->history = new History();

        $this->guzzleClient->getEmitter()->attach($this->history);
    }

    /** {@inheritdoc} */
    protected function tearDown()
    {
        $this->logger = null;
        $this->guzzleClient = null;
        $this->history = null;
        $this->adapter = null;

        parent::tearDown();
    }

    private function thereIsSuccessfullFacebookApiResponse()
    {
        $body = array(
            'access_token' => 'access-token',
            'token_type' => 'bearer',
            'expires' => (string) (time() + 100),
        );

        $this->mockResponse(200, json_encode($body));
    }

    private function thereIsFacebookApiResponseWithWrongToken()
    {
        $body = array(
            'xyz' => 'abcd',
            'expires' => (string) (time() + 100),
        );

        $this->mockResponse(200, http_build_query($body));
    }

    private function thereIsFacebookApiUnsuccessfulResponse()
    {
    }

    private function thereIsFacebookApiResponseWithWrongJson()
    {
        $this->mockResponse(200, 'xyz');
    }

    private function thereIsFacebookApiException()
    {
        $this->mockResponse(500);
    }

    private function accessToken()
    {
        return 'CAANBRgGtA1EBAJNFu6pX7Co3z0nv8vARhu15fZA2nh1N11lzcFlY6rBgRuxZBkjsAZBRSoDFS4DymPU2xGp4aN3GrH4T9FfZA5QAlBZA4ImYgZAs0ZC3FWkMq0iLLjP1H2DXeZBBBfiPMtzSkjqhpN7MP34bFBd35wwLPjZBB0Ij3Y0ZA3f3hWqaCs1rReJYpowfIZD';
    }

    private function expectedSuccessfulAccessTokenRequest()
    {
        $request = new Request('GET', 'https://graph.facebook.com/v2.4/oauth/access_token');
        $query = $request->getQuery();

        $query->set('client_id', '1234');
        $query->set('redirect_uri', 'http://localhost/facebook/login');
        $query->set('client_secret', 'secret');
        $query->set('code', 'correct-code');

        return $request;
    }

    private function assertThatRequestIsEqual(RequestInterface $expectedRequest, RequestInterface $request)
    {
        $this->assertEquals($expectedRequest->getMethod(), $request->getMethod());
        $this->assertEquals($expectedRequest->getUrl(), $request->getUrl());
    }

    private function assertThatUserNodeContainsRequiredFields(array $requiredFields, array $userNode)
    {
        foreach ($requiredFields as $requiredField) {
            $this->assertArrayHasKey($requiredField, $userNode);
            $this->assertNotNull($userNode[$requiredField]);
        }
    }

    private function assertThatUserNodeContainsId($userNode)
    {
        $this->assertArrayHasKey('id', $userNode);
        $this->assertNotNull($userNode['id']);
    }

    private function assertThatUserNodeContainsName($userNode)
    {
        $this->assertArrayHasKey('name', $userNode);
        $this->assertNotNull($userNode['name']);
    }

    private function assertThatLogWithMessageWasCreated($expectedMessage)
    {
        $logWasCreated = false;
        foreach ($this->logger->getLogs() as $log) {
            if (false !== strpos($log['message'], $expectedMessage)) {
                $logWasCreated = true;
                break;
            }
        }

        $this->assertTrue($logWasCreated);
    }

    private function mockResponse($status, $body = null)
    {
        $mock = new Mock();
        if ($status === 200) {
            $mock->addResponse(new Response($status, array(), ($body === null) ? null : Stream::factory($body)));
        } else {
            $mock->addException(new RequestException('Exception', new Request('GET', 'http://graph.facebook.com/xyz')));
        }

        $this->guzzleClient->getEmitter()->attach($mock);
    }

    private function guzzleFacebookApiHasLoggerEnabled()
    {
        $this->adapter = new GuzzleFacebookApi($this->guzzleClient, 'http://localhost/facebook/login', '1234', 'secret', $this->logger);
    }
}
