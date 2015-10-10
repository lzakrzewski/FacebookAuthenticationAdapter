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
    /** @var FakeLogger */
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
    public function it_can_retrieve_me_fields_successfully()
    {
        $fields = $this->adapter->me($this->accessToken());

        $this->assertThatRequiredMeFieldsExists($fields);
    }

    /**
     * @test
     *
     * @expectedException \Lucaszz\FacebookAuthenticationAdapter\Adapter\FacebookApiException
     */
    public function it_fails_when_unable_to_parse_json_response_during_retrieving_me_fields()
    {
        $this->thereIsFacebookApiResponseWithWrongJson();

        $this->adapter->me($this->accessToken());
    }


    /** @test */
    public function it_logs_when_unable_to_parse_json_response_during_retrieving_me_fields()
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

    /**
     * @test
     *
     * @expectedException \Lucaszz\FacebookAuthenticationAdapter\Adapter\FacebookApiException
     */
    public function it_fails_when_facebook_api_returns_me_without_required_fields()
    {
        $this->thereIsFacebookApiWithoutRequiredMeFields();

        $this->adapter->me($this->accessToken());
    }

    /** @test */
    public function it_logs_when_facebook_api_returns_me_without_required_fields()
    {
        $this->thereIsFacebookApiWithoutRequiredMeFields();
        $this->guzzleFacebookApiHasLoggerEnabled();

        try {
            $this->adapter->me($this->accessToken());
        } catch (\Exception $e) {
        }

        $this->assertThatLogWithMessageWasCreated('Facebook graph api should return response with all required fields');
    }

    /** {@inheritdoc} */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = new FakeLogger();
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
            'expires' => (string) (time() + 100),
        );

        $this->mockResponse(200, http_build_query($body));
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

    private function thereIsFacebookApiWithoutRequiredMeFields()
    {
        $this->mockResponse(200, json_encode(array('id' => '12345', 'name' => 'xyz')));
    }

    private function thereIsFacebookApiException()
    {
        $this->mockResponse(500);
    }

    private function accessToken()
    {
        $file = 'https://gist.githubusercontent.com/Lucaszz/a36984dd6691ab53092d/raw/e1b3c9168f1ea0e4e29c1e659627a4732ba5ce85/accessToken_1440969823';

        if ($resource = fopen($file, 'r')) {
            $accessToken = fgets($resource);
            fclose($resource);

            return $accessToken;
        }

        throw new \Exception('Unable to read file with access token');
    }

    private function expectedSuccessfulAccessTokenRequest()
    {
        $request = new Request('GET', 'https://graph.facebook.com/oauth/access_token');
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

    private function assertThatRequiredMeFieldsExists(array $fields)
    {
        $this->assertArrayHasKey('id', $fields);
        $this->assertNotNull($fields['id']);

        $this->assertArrayHasKey('email', $fields);
        $this->assertNotNull($fields['email']);

        $this->assertArrayHasKey('name', $fields);
        $this->assertNotNull($fields['name']);
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
