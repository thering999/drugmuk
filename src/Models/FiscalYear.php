<?php

namespace App\Models;

use App\Core\Model;

class FiscalYear extends Model {
    protected $table = 'fiscal_years';

    /**
     * Get all fiscal years
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY year DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getActiveYears() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY year DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
