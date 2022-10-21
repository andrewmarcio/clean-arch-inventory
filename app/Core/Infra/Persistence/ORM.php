<?php
declare(strict_types=1);

namespace App\Core\Infra\Persistence;

use App\Support\Traits\ValidateEntityProperties;
use PDO;

abstract class ORM extends DatabaseConnection
{
    use ValidateEntityProperties;

    protected array $query = [];

    public function checkQueryIsEmpty(): void
    {
        if (empty($this->query)) {
            $this->select();
        }
    }

    private function getTable(): string
    {
        return $this->table;
    }

    public function select(array $columns = ["*"]): self
    {
        $columns = implode(",", array_filter($columns));
        $this->query = [
            "SELECT",
            $columns,
            "FROM",
            $this->getTable(),
        ];

        return $this;
    }

    public function where(string $column, string $condition, string $value): self
    {
        $this->checkQueryIsEmpty();

        if (!in_array("WHERE", $this->query)) {
            $this->query = array_merge($this->query, ["WHERE"]);
        } else {
            $this->query = array_merge($this->query, ["AND"]);
        }

        $this->query = array_merge($this->query, [
            $column,
            $condition,
            str_pad($value, (strlen($value) + 2), "'", STR_PAD_BOTH),
        ]);

        return $this;
    }

    public function latest(string $column = "created_at"): self
    {
        $this->checkQueryIsEmpty();
        
        $this->query = array_merge($this->query, [
            "ORDER BY",
            $column,
            "DESC"
        ]);
        
        return $this;
    }

    public function first()
    {
        $this->checkQueryIsEmpty();
        $this->query = array_merge($this->query, ["LIMIT", 1]);

        $this->executeQuery();
        
        if(!$data = $this->data->fetchObject(get_class($this)))
            throw new \Exception("Entity is not found");

        return $data;
    }

    public function get(): array
    {
        $this->checkQueryIsEmpty();

        $this->executeQuery();
        
        if(!$data = $this->data->fetchAll(PDO::FETCH_CLASS, get_class($this)))
            return [];
        
        return $data;
    }

    public function create(array $data = []): self
    {
        $data = $this->validateProperties($data);

        $this->query = [
            "INSERT INTO",
            $this->getTable(),
            "(",
            implode(",", array_filter(array_keys($data))),
            ")",
            "VALUES",
            "(",
            implode(",", str_split(str_pad("", count($data), "?"))),
            ")",
        ];

        $this->executeQuery(array_values($data));
        
        return self::getInstance()->latest()->first();
    }

    public function update(array $data = []): self
    {
        $data = $this->validateProperties($data);

        $this->query = [
            "UPDATE",
            $this->getTable(),
            "SET",
            implode(", ", array_map(function ($property){
                return str_pad($property, (strlen($property)+4), " = ?");
            }, array_keys($data))),
            "WHERE",
            "id",
            "=",
            "?"
        ];

        $this->executeQuery(array_merge(array_values($data), [$this->id]));
        unset($this->attributes["data"]);

        return self::getInstance()->where("id", "=", $this->id)->first();
    }

    public function delete(): bool
    {
        try {
            $this->query = [
                "DELETE FROM",
                $this->getTable(),
                "WHERE",
                "id",
                "=",
                "?",
            ];
    
            $this->executeQuery([$this->id]);
            unset($this->attributes);
            
            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function executeQuery(?array $params = null): void
    {
        $this->data = $this->getInstance()
            ->getConnection()
            ->prepare(implode(" ", $this->query));
        
        $this->data->execute($params);
        $this->query = [];
    }
}
