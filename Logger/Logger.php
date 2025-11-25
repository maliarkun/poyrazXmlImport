<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Logger;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger extends MonologLogger
{
    public function __construct(
        string $name = 'poyraz_xml_import',
        array $handlers = [],
        array $processors = []
    ) {
        // Eğer dışarıdan handler verilmediyse, varsayılan olarak kendi log dosyamızı ekleyelim
        if (empty($handlers)) {
            $handlers[] = new StreamHandler(
                BP . '/var/log/poyraz_xml.log',
                MonologLogger::INFO
            );
        }

        parent::__construct($name, $handlers, $processors);
    }
}