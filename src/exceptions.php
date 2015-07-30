<?php

namespace Carrooi\Security;

class RuntimeException extends \RuntimeException {}

class LogicException extends \LogicException {}

class InvalidStateException extends RuntimeException {}

class NotImplementedException extends LogicException {}

class StrictModeException extends LogicException {}
