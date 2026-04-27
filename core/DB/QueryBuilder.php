<?php

declare(strict_types=1);

namespace Siro\Core\DB;

use RuntimeException;
use Siro\Core\Database;

final class QueryBuilder
{
    private string $table = '';
    /** @var array<int, string> */
    private array $columns = ['*'];
    /** @var array<int, array{boolean:string,column:string,operator:string,param:string}> */
    private array $wheres = [];
    /** @var array<string, mixed> */
    private array $bindings = [];
    /** @var array<int, array{column:string,direction:string}> */
    private array $orders = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    private int $whereCounter = 0;

    public function __construct(string $table)
    {
        $this->table($table);
    }

    public function table(string $table): self
    {
        $this->table = trim($table);

        if ($this->table === '') {
            throw new RuntimeException('QueryBuilder table name cannot be empty.');
        }

        return $this;
    }

    public function select(array|string $columns): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $normalized = [];
        foreach ($columns as $column) {
            $column = trim((string) $column);
            if ($column !== '') {
                $normalized[] = $column;
            }
        }

        $this->columns = $normalized === [] ? ['*'] : $normalized;
        return $this;
    }

    public function where(string $column, mixed $value): self
    {
        return $this->addWhere('AND', $column, $value);
    }

    public function orWhere(string $column, mixed $value): self
    {
        return $this->addWhere('OR', $column, $value);
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $dir = strtoupper(trim($direction)) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = ['column' => trim($column), 'direction' => $dir];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);
        return $this;
    }

    /** @return array<int, array<string, mixed>> */
    public function get(): array
    {
        [$sql, $bindings] = $this->buildSelectQuery();
        return Database::select($sql, $bindings);
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limit(1);
        $rows = $clone->get();
        return $rows[0] ?? null;
    }

    public function insert(array $data): int|string
    {
        if ($data === []) {
            return 0;
        }

        $columns = [];
        $holders = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $name = 'i_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $column);
            $columns[] = (string) $column;
            $holders[] = ':' . $name;
            $bindings[$name] = $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $holders)
        );

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($bindings);

        $lastId = Database::connection()->lastInsertId();
        return $lastId !== '0' ? $lastId : $stmt->rowCount();
    }

    public function update(array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $sets = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $name = 'u_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $column);
            $sets[] = sprintf('%s = :%s', $column, $name);
            $bindings[$name] = $value;
        }

        [$whereSql, $whereBindings] = $this->compileWhere();
        $sql = sprintf('UPDATE %s SET %s%s', $this->table, implode(', ', $sets), $whereSql);

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([...$bindings, ...$whereBindings]);

        return $stmt->rowCount();
    }

    public function delete(): int
    {
        [$whereSql, $whereBindings] = $this->compileWhere();
        $sql = sprintf('DELETE FROM %s%s', $this->table, $whereSql);

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($whereBindings);

        return $stmt->rowCount();
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function paginate(int $perPage): array
    {
        $perPage = max(1, $perPage);
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        [$countSql, $whereBindings] = $this->buildCountQuery();
        $countStmt = Database::connection()->prepare($countSql);
        $countStmt->execute($whereBindings);
        $total = (int) ($countStmt->fetchColumn() ?: 0);

        $clone = clone $this;
        $rows = $clone->limit($perPage)->offset($offset)->get();

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return [
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    private function addWhere(string $boolean, string $column, mixed $value): self
    {
        $param = 'w_' . $this->whereCounter;
        $this->whereCounter++;

        $this->wheres[] = [
            'boolean' => $boolean,
            'column' => trim($column),
            'operator' => '=',
            'param' => $param,
        ];
        $this->bindings[$param] = $value;

        return $this;
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function buildSelectQuery(): array
    {
        [$whereSql, $whereBindings] = $this->compileWhere();
        $columns = implode(', ', $this->columns);
        $sql = sprintf('SELECT %s FROM %s%s', $columns, $this->table, $whereSql);

        if ($this->orders !== []) {
            $orderParts = [];
            foreach ($this->orders as $order) {
                $orderParts[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return [$sql, $whereBindings];
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function buildCountQuery(): array
    {
        [$whereSql, $whereBindings] = $this->compileWhere();
        $sql = sprintf('SELECT COUNT(*) AS aggregate FROM %s%s', $this->table, $whereSql);
        return [$sql, $whereBindings];
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function compileWhere(): array
    {
        if ($this->wheres === []) {
            return ['', []];
        }

        $parts = [];
        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? '' : ' ' . $where['boolean'] . ' ';
            $parts[] = $prefix . $where['column'] . ' ' . $where['operator'] . ' :' . $where['param'];
        }

        return [' WHERE ' . implode('', $parts), $this->bindings];
    }
}
