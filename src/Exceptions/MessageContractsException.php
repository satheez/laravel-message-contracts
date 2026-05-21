<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

use RuntimeException;

/**
 * Base exception for all Laravel Message Contracts package exceptions.
 *
 * Catch this type to handle any package error in one place:
 *
 *   catch (MessageContractsException $e) { ... }
 */
class MessageContractsException extends RuntimeException {}
