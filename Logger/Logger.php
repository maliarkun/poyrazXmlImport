<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Logger;

use Monolog\Logger as MonologLogger;

if (!class_exists(\Poyraz\XmlImport\Logger\Logger::class, false)) {
    class Logger extends MonologLogger
    {
    }
}
