<?php

namespace Lucaszz\FacebookAuthenticationAdapter\Tests\Adapter;

use Psr\Log\AbstractLogger;

class FakeLogger extends AbstractLogger
{
    /** @var array */
    private static $logs;

    /** {@inheritdoc} */
    public function getLogs()
    {
        return static::$logs;
    }

    /** {@inheritdoc} */
    public function countErrors()
    {
        return 0;
    }

    /** {@inheritdoc} */
    public function log($level, $message, array $context = array())
    {
        static::$logs[] = array(
            'message' => $message,
        );
    }
}
