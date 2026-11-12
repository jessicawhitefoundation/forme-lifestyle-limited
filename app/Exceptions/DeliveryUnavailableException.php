<?php

namespace App\Exceptions;

use RuntimeException;

/** No delivery price could be found for the address, so the order can't be taken. */
class DeliveryUnavailableException extends RuntimeException {}
