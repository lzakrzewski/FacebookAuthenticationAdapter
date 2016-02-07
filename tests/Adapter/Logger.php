<?php

namespace Lzakrzewski\FacebookAuthenticationAdapter\Tests\Adapter;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /** @var array */
    private $logs;

    /** {@inheritdoc} */
    public function getLogs()
    {
        return $this->logs;
    }

    /** {@inheritdoc} */
    public function countErrors()
    {
        return 0;
    }

    /** {@inheritdoc} */
    public function log($level, $message, array $context = array())
    {
        $this->logs[] = array(
            'message' => $message,
        );
    }
}
