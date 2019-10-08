<?php

namespace TeamA\Lock;

use Closure;
use PDO;
use PDOException;

abstract class AbstractDb
{
    public const NAMESPACE = '';

    protected const LOCK_PDO_EXCEPTION_MESSAGE_SUBSTRINGS = [
        '3133',
        'Service lock wait timeout exceeded'
    ];

    /**
     * @var null | Closure
     */
    protected static $_pdoPromise = null;

    /**
     * @var string
     */
    private $_key = '';

    protected function __construct(array $scalarParams = [])
    {
        array_unshift($scalarParams, static::class);
        array_unshift($scalarParams, static::NAMESPACE);

        $key = join('|', $scalarParams);

        $this->_key = md5($key);
    }

    public static function setPdoPromise(Closure $pdoPromise) : void
    {
        self::$_pdoPromise = $pdoPromise;
    }

    final public function getKey() : string
    {
        return $this->_key;
    }

    protected static function _getPdoPromise() : Closure
    {
        return self::$_pdoPromise;
    }

    protected static function _getPdo() : ? PDO
    {
        return self::_getPdoPromise() === null ? null : self::_getPdoPromise();
    }

    protected function _query(string $query, array $params = []) : ? string
    {
        $pdo = self::_getPdo();

        if ($pdo === null) {
            throw new \Exception('PDO object must be injected before');
        }

        $statement = $pdo->prepare($query);

        foreach ($params as $name => $value) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }

            $statement->bindValue($name, $value, $type);
        }

        $statement->execute();

        $result = $statement->fetchColumn();

        if ($result === false || $result === null) {
            return null;
        }

        return (string) $result;
    }

    /**
     * @throws TimeoutException | \Exception
     */
    protected function _convertLockException(\Exception $e, int $timeout, bool $writeMode = true) : void
    {
        while (true) {
            if ($e === null || $e instanceof PDOException) {
                break;
            }

            $e = $e->getPrevious();
        }

        if (
            $e instanceof PDOException && (
                strpos($e->getMessage(), '3133') ||
                strpos($e->getMessage(), 'Service lock wait timeout exceeded')
            )
        ) {
            foreach (self::LOCK_PDO_EXCEPTION_MESSAGE_SUBSTRINGS as $substr) {
                if (strpos($e->getMessage(), $substr) !== false) {
                    throw new TimeoutException(static::class, $timeout, $writeMode);
                }
            }
        }

        throw $e;
    }

}