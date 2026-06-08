<?php
/**
 * @author Selcuk Mart
 * 26.07.2022
 * 09:58
 */

namespace App\CustomLogger;

use App\CustomLogger\LoggerHelpers\LoggerFileStream;
use Closure;
use DateTime;
use DateTimeInterface;
use Psr\Log\AbstractLogger;
use Stringable;
use function get_class;
use function gettype;
use function is_object;
use function is_resource;
use function is_scalar;
use const PHP_EOL;

class CustomLogger extends AbstractLogger
{

    private const LEVELS = [
        CustomLogLevel::DEBUG => 0,
        CustomLogLevel::INFO => 1,
        CustomLogLevel::NOTICE => 2,
        CustomLogLevel::WARNING => 3,
        CustomLogLevel::ERROR => 4,
        CustomLogLevel::CRITICAL => 5,
        CustomLogLevel::ALERT => 6,
        CustomLogLevel::EMERGENCY => 7,
        CustomLogLevel::CUSTOM => 8,
    ];

    private int $minLevelIndex;
    private Closure $formatter;

    /** @var resource|null */
    private $handle;

    /**
     * @param string|resource|null $output
     */
    public function __construct(string $minLevel = 'debug', $output = null, callable $formatter = null)
    {
        if (!isset(self::LEVELS[$minLevel])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $minLevel));
        }

        $this->minLevelIndex = self::LEVELS[$minLevel];
        $this->formatter = null !== $formatter ? $formatter(...) : $this->format(...);
        if ($output && false === $this->handle = is_resource($output) ? $output : @fopen($output, 'a')) {
            throw new InvalidArgumentException(sprintf('Unable to open "%s".', $output));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        if (!isset(self::LEVELS[$level])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        $formatter = $this->formatter;
        @fwrite(LoggerFileStream::getInstance()->getStream(), $formatter($level, $message, $context));
    }

    private function format(string $level, string $message, array $context, bool $prefixDate = true): string
    {
        if (str_contains($message, '{')) {
            $replacements = [];
            foreach ($context as $key => $val) {
                if (null === $val || is_scalar($val) || $val instanceof Stringable) {
                    $replacements["{{$key}}"] = $val;
                } elseif ($val instanceof DateTimeInterface) {
                    $replacements["{{$key}}"] = $val->format(DateTime::RFC3339);
                } elseif (is_object($val)) {
                    $replacements["{{$key}}"] = '[object '. get_class($val).']';
                } else {
                    $replacements["{{$key}}"] = '['. gettype($val).']';
                }
            }

            $message = strtr($message, $replacements);
        }

        $log = sprintf('[%s] %s', $level, $message). PHP_EOL;
        if ($prefixDate) {
            $log = date(DateTime::RFC3339).' '.$log;
        }

        return $log;
    }

    public function custom(string|\Stringable $message, array $context = []): void
    {
        $this->log(CustomLogLevel::CUSTOM, $message, $context);
    }
}