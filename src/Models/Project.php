<?php

namespace App\Models;

use App\Database\Database;
use PDO;

class Project
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM projects WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO projects (name, url, platform, status, description) 
                VALUES (:name, :url, :platform, :status, :description)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':url' => $data['url'],
            ':platform' => $data['platform'] ?? 'Other',
            ':status' => $data['status'] ?? 'development',
            ':description' => $data['description'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->getById($id)) return false;

        // Динамическое построение запроса UPDATE
        $setParts = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            // Исключаем ID и даты из обновления вручную
            if (in_array($key, ['id', 'created_at', 'updated_at'])) continue;
            
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($setParts)) return false;

        $sql = "UPDATE projects SET " . implode(', ', $setParts) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        if (!$this->getById($id)) return false;

        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
