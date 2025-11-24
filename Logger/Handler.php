<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Logger;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;

if (!class_exists(\Poyraz\XmlImport\Logger\Handler::class, false)) {
    class Handler extends BaseHandler
    {
        /**
         * @var string
         */
        protected $fileName = '/var/log/poyraz_xml.log';

        /**
         * @var int
         */
        protected $loggerType = MonologLogger::INFO;
    }
}
