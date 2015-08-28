<?php

namespace Carrooi\Security;

class RuntimeException extends \RuntimeException {}

class LogicException extends \LogicException {}

class InvalidArgumentException extends \InvalidArgumentException {}

class InvalidStateException extends RuntimeException {}

class NotImplementedException extends LogicException {}

class StrictModeException extends LogicException {}

class AuthorizatorClassNotExistsException extends LogicException {}

class AuthorizatorInvalidTypeException extends LogicException {}

class UnknownResourceObjectException extends LogicException {}
