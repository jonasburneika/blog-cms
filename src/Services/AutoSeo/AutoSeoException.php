<?php

namespace BlogCms\Services\AutoSeo;

/**
 * Thrown when the AutoSEO API is misconfigured or a request fails
 * (non-2xx response, connection error, or unexpected payload shape).
 */
class AutoSeoException extends \RuntimeException
{
}
