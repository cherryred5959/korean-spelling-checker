<?php

declare(strict_types = 1);

namespace Cherry\RealTimeClient;

/**
 * Interface ClientInterface
 * @package Cherry\RealTimeClient
 */
interface ClientInterface
{
    public function __construct();

    public function login();

    public function connect();
}