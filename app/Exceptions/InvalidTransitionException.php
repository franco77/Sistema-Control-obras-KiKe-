<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Transición de estado no permitida por la máquina de estados. */
class InvalidTransitionException extends RuntimeException {}