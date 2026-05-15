<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'slug',
        'name',
        'privileges',
        'dashboard_type',
        'is_system',
        'sort_order',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findBySlug(string $slug): ?array
    {
        $slug = strtoupper(trim($slug));

        return $this->where('slug', $slug)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForSelect(): array
    {
        return $this->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
    }

    /**
     * @return list<string>
     */
    public function privilegesForSlug(string $slug): array
    {
        $row = $this->findBySlug($slug);
        if (! $row) {
            return [];
        }
        $parsed = json_decode((string) ($row['privileges'] ?? ''), true);

        return is_array($parsed) ? $parsed : [];
    }

    public static function slugFromName(string $name): string
    {
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', trim($name)) ?? '');
        $slug = trim($slug, '_');

        return $slug !== '' ? $slug : 'ROLE_' . time();
    }
}
