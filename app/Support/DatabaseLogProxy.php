<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * @method void debug(string|array $message, array $context = [])
 * @method void info(string|array $message, array $context = [])
 * @method void notice(string|array $message, array $context = [])
 * @method void warning(string|array $message, array $context = [])
 * @method void error(string|array $message, array $context = [])
 * @method void critical(string|array $message, array $context = [])
 * @method void alert(string|array $message, array $context = [])
 * @method void emergency(string|array $message, array $context = [])
 */
readonly class DatabaseLogProxy
{
    public function __construct(private string $loggerName) {}

    public function __call(string $method, array $parameters): mixed
    {
        [$message, $context] = [$parameters[0] ?? '', $parameters[1] ?? []];

        // Walk up the backtrace to find the true application caller (skipping vendor & framework internals)
        $callerFile = null;
        $callerLine = null;

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $file = $frame['file'] ?? '';

            // Skip framework vendor files, Monolog, and our own proxy/trait files
            if (
                $file &&
                !str_contains($file, 'vendor/laravel') &&
                !str_contains($file, 'vendor/monolog') &&
                !str_contains($file, 'DatabaseLogProxy.php') &&
                !str_contains($file, 'InteractsWithDatabaseLog.php')
            ) {
                $callerFile = $file;
                $callerLine = $frame['line'] ?? null;
                break; // Found the user's application file!
            }
        }

        // Merge the logger name and true caller coordinates into the context
        $context = array_merge([
            'name'   => $this->loggerName,
            '_file'  => $callerFile,
            '_line'  => $callerLine,
        ], $context);

        return Log::channel('log_stack')->{$method}($message, $context);
    }
}
