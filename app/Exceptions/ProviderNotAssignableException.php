<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** El proveedor no cumple los requisitos para recibir trabajo. */
class ProviderNotAssignableException extends RuntimeException {}