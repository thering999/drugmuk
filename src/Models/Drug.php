<?php

namespace App\Models;

use App\Core\Model;

class Drug extends Model {
    protected $table = 'drugs';

    /**
     * Get all drugs
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get drug by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get active drugs
     */
    public function getActiveDrugs() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Search drugs by name or code
     */
    public function search($keyword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE :keyword OR code LIKE :keyword) 
                AND is_active = 1
                ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['keyword' => "%$keyword%"]);
        return $stmt->fetchAll();
    }

    /**
     * Create new drug
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (code, name, generic_name, unit, pack_size, price, min_stock, max_stock, category, is_active)
                VALUES (:code, :name, :generic_name, :unit, :pack_size, :price, :min_stock, :max_stock, :category, :is_active)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Update drug
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET
                code = :code,
                name = :name,
                generic_name = :generic_name,
                unit = :unit,
                pack_size = :pack_size,
                price = :price,
                min_stock = :min_stock,
                max_stock = :max_stock,
                category = :category,
                is_active = :is_active
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete (soft delete) drug
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get drugs by category
     */
    public function getByCategory($category) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE category = :category AND is_active = 1
                ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['category' => $category]);
        return $stmt->fetchAll();
    }
}
