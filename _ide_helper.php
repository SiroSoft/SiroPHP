<?php

/**
 * IDE Helper for SiroPHP facades.
 *
 * This file provides autocomplete and type hinting for static facade methods.
 * It is auto-generated and should not be edited manually.
 *
 * @package Siro\Core
 */

namespace Siro\Core {
    /**
     * @method static \Siro\Core\Response success(mixed $data, string $message = 'OK')
     * @method static \Siro\Core\Response created(mixed $data, string $message = 'Created')
     * @method static \Siro\Core\Response error(string $message, int $code = 400, array $errors = [])
     * @method static \Siro\Core\Response noContent()
     * @method static \Siro\Core\Response paginated(array $data, array $meta, string $message)
     * @method static \Siro\Core\Response raw(string $content, string $contentType = 'text/plain')
     * @method static \Siro\Core\Response json(array $data)
     */
    class Response {}
}

namespace Siro\Core {
    /**
     * @method static \Siro\Core\Route get(string $path, callable|array $handler)
     * @method static \Siro\Core\Route post(string $path, callable|array $handler)
     * @method static \Siro\Core\Route put(string $path, callable|array $handler)
     * @method static \Siro\Core\Route patch(string $path, callable|array $handler)
     * @method static \Siro\Core\Route delete(string $path, callable|array $handler)
     * @method static \Siro\Core\Route options(string $path, callable|array $handler)
     * @method static \Siro\Core\RouteResource resource(string $name, string $controller, array $middleware = [])
     * @method static \Siro\Core\Router group(string $prefix, array $middleware, callable $callback)
     */
    class Route {}
}

namespace Siro\Core {
    /**
     * @method static \PDO connection(?string $name = null)
     * @method static mixed select(string $sql, array $params = [], ?string $connection = null)
     * @method static mixed first(string $sql, array $params = [], ?string $connection = null)
     * @method static int execute(string $sql, array $params = [], ?string $connection = null)
     * @method static \Siro\Core\DB\QueryBuilder table(string $table, ?string $connection = null)
     * @method static mixed transaction(callable $callback, ?string $connection = null)
     * @method static void beginTransaction(?string $connection = null)
     * @method static void commit(?string $connection = null)
     * @method static void rollBack(?string $connection = null)
     */
    class DB {}
}

namespace Siro\Core {
    /**
     * @method static \PDO connection(?string $name = null)
     * @method static \Siro\Core\DB\QueryBuilder table(string $table)
     * @method static \Siro\Core\DB\RawExpression raw(string $value)
     * @method static array<int, array<string, mixed>> select(string $sql, array $params, ?string $connection)
     * @method static int execute(string $sql, array $params, ?string $connection)
     */
    class Database {}
}

namespace Siro\Core {
    /**
     * @method static mixed get(string $key, mixed $default = null)
     * @method static void set(string $key, mixed $value, int $ttl = 3600)
     * @method static bool has(string $key)
     * @method static bool forget(string $key)
     * @method static bool flush()
     * @method static bool remember(string $key, int $ttl, callable $callback)
     */
    class Cache {}
}

namespace Siro\Core {
    /**
     * @method static void dispatch(object|string $event, mixed $payload = null)
     * @method static void listen(string $event, callable|string $listener)
     * @method static void listenOnce(string $event, callable $listener)
     * @method static void flush()
     * @method static bool hasListeners(string $event)
     */
    class Event {}
}

namespace Siro\Core {
    /**
     * @method static void debug(string $message, array $context = [])
     * @method static void info(string $message, array $context = [])
     * @method static void notice(string $message, array $context = [])
     * @method static void warning(string $message, array $context = [])
     * @method static void error(string $message, array $context = [])
     * @method static void critical(string $message, array $context = [])
     * @method static void alert(string $message, array $context = [])
     * @method static void emergency(string $message, array $context = [])
     * @method static \Siro\Core\Logger channel(string $name)
     * @method static void setContext(array $context)
     */
    class Logger {}
}

namespace Siro\Core {
    /**
     * @method static string make(string $value, array $options = [])
     * @method static bool check(string $value, string $hash)
     * @method static bool needsRehash(string $hash, array $options = [])
     * @method static array info(string $hash)
     */
    class Hash {}
}

namespace Siro\Core {
    /**
     * @method static string encrypt(string $data, ?string $key = null)
     * @method static string decrypt(string $payload, ?string $key = null)
     */
    class Encrypter {}
}

namespace Siro\Core {
    /**
     * @method static bool put(string $path, mixed $contents, array $options = [])
     * @method static string|false get(string $path)
     * @method static bool exists(string $path)
     * @method static bool delete(string $path)
     * @method static bool copy(string $from, string $to)
     * @method static bool move(string $from, string $to)
     * @method static string url(string $path)
     * @method static int|false size(string $path)
     * @method static string|false mimeType(string $path)
     * @method static int|false lastModified(string $path)
     * @method static array files(string $directory)
     * @method static array allFiles(string $directory)
     * @method static bool makeDirectory(string $path)
     * @method static bool deleteDirectory(string $path)
     */
    class Storage {}
}

namespace Siro\Core {
    /**
     * @method static \Siro\Core\Session instance()
     * @method static void start()
     * @method static string getId()
     * @method static void setId(string $id)
     * @method static mixed get(string $key, mixed $default = null)
     * @method static void set(string $key, mixed $value)
     * @method static bool has(string $key)
     * @method static void remove(string $key)
     * @method static void clear()
     * @method static void regenerate(bool $deleteOld = false)
     * @method static void invalidate()
     * @method static void flash(string $key, mixed $value)
     * @method static string token()
     * @method static bool validateCsrf(string $token)
     */
    class Session {}
}

namespace Siro\Core {
    /**
     * @method static string slug(string $value, string $separator = '-')
     * @method static string limit(string $value, int $limit = 100, string $end = '...')
     * @method static string camel(string $value)
     * @method static string studly(string $value)
     * @method static string snake(string $value)
     * @method static string kebab(string $value)
     * @method static bool contains(string $haystack, string $needle)
     * @method static bool startsWith(string $haystack, string $needle)
     * @method static bool endsWith(string $haystack, string $needle)
     * @method static string after(string $value, string $search)
     * @method static string before(string $value, string $search)
     * @method static string random(int $length = 16)
     * @method static string plural(string $value)
     * @method static string singular(string $value)
     */
    class Str {}
}
