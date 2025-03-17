<?php /** @noinspection SqlNoDataSourceInspection */

namespace Neuron\Database\Drivers;

use Neuron\Database\AbstractDatabaseDriver;
use Neuron\Database\DatabaseDriver;
use Neuron\Database\DatabaseInterface;
use Neuron\Database\FetchMode;
use Neuron\Database\ParameterType;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Throwable;

#[DatabaseDriver]
class MysqlDatabaseDriver extends AbstractDatabaseDriver
{
    private DatabaseInterface $database;
    private PDO $pdo;
    public function __construct(LoggerInterface $logger, DatabaseInterface $_database, string $database, string $host = 'localhost', int $port = 3306, string $username = 'root', string $password = '', string $charset = 'utf8')
    {
        parent::__construct($logger);
        $this->database = $_database;
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
        $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=$charset", $username, $password, $options);
    }

    private function getPDOParamType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    private function prepare(string $query, array $parameters): PDOStatement
    {
        $statement = $this->pdo->prepare($query);

        if (empty($parameters)) {
            return $statement;
        }

        $parameterType = array_is_list($parameters) ? ParameterType::Positional : ParameterType::Named;

        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                 $parameterType == ParameterType::Positional ? $key + 1 : $key,
                $value,
                $this->getPDOParamType($value)
            );
        }

        return $statement;
    }

    /**
     * @inheritDoc
     */
    public function count(string $query, array $parameters = []): int
    {
        $start = microtime(true);
        if (!preg_match('/^\sSELECT \b/i', $query)) {
            return 0;
        }
        $result = $this->fetchScalar('SELECT COUNT(*) FROM (' . $query . ') ___t;', $parameters);
        $this->database->profileQuery('COUNT', $query, $parameters, $this, microtime(true) - $start, $this->pdo->errorCode() === null ? false : $this->pdo->errorInfo());
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query, array $parameters = []): int|bool
    {
        $start = microtime(true);
        $statement = $this->prepare($query, $parameters);
        $success = $statement->execute();

        // If it's an INSERT, return the last insert ID
        if (preg_match('/^\s*(INSERT|REPLACE)\b/i', $query)) {
            $result = $this->pdo->lastInsertId();
        } else {
            $result = $success;
        }

        $this->database->profileQuery('EXECUTE', $query, $parameters, $this, microtime(true) - $start, $this->pdo->errorCode() === null ? false : $this->pdo->errorInfo());

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function fetchScalar(string $query, array $parameters = [], mixed $default = null): mixed
    {
        $start = microtime(true);
        $row = $this->fetchOne($query, $parameters);
        if ($row === false || count($row) == 0)
        {
            $row = [$default];
        }
        $result = array_values($row)[0];
        $this->database->profileQuery('FETCH_SCALAR', $query, $parameters, $this, microtime(true) - $start, $this->pdo->errorCode() === null ? false : $this->pdo->errorInfo());
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function fetchOne(string $query, array $parameters = []): array|false
    {
        $start = microtime(true);
        $statement = $this->prepare($query, $parameters);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $this->database->profileQuery('FETCH_ONE', $query, $parameters, $this, microtime(true) - $start, $this->pdo->errorCode() === null ? false : $this->pdo->errorInfo());
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function fetchAll(string $query, array $parameters = [], FetchMode $fetchMode = FetchMode::FETCH_ASSOC): array
    {
        $start = microtime(true);
        $statement = $this->prepare($query, $parameters);
        $statement->execute();
        $result = $statement->fetchAll($fetchMode);
        $this->database->profileQuery('FETCH_ALL', $query, $parameters, $this, microtime(true) - $start, $this->pdo->errorCode() === null ? false : $this->pdo->errorInfo());
        return $result;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $start = microtime(true);
        $result = false;
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this);
            $this->pdo->commit();
        } catch (Throwable $t) {
            $this->pdo->rollBack();
            $this->logger->error($t->getMessage());
        }
        $this->database->profileQuery('TRANSACTION', '', [], $this, microtime(true) - $start, $this->pdo->errorCode() === null ? false : $this->pdo->errorInfo());
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * @inheritDoc
     */
    public function ping(): bool
    {
        $this->pdo->lastInsertId();
        return true;
    }
}