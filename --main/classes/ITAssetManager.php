<?php
/**
 * Class 1 (คนที่ 1): ITAssetManager — จัดการข้อมูลอุปกรณ์ IT
 * หน้าที่: ควบคุมและเช็คข้อมูลอุปกรณ์ IT ทั้งหมดในองค์กร
 * ฟังก์ชันหลัก:
 * 1. getAssetDetails($assetId): ค้นหาข้อมูลอุปกรณ์และเช็คประกัน (ในประกัน / หมดประกัน)
 * 2. updateAssetStatus($assetId, $status): อัปเดตสถานะของอุปกรณ์ (เช่น Normal / Repairing / Damaged)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Asset.php';

class ITAssetManager {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * ค้นหาข้อมูลอุปกรณ์ และเช็คสถานะประกัน
     */
    public function getAssetDetails(string $assetId): ?Asset {
        $stmt = $this->pdo->prepare("SELECT * FROM assets WHERE asset_id = :asset_id LIMIT 1");
        $stmt->execute([':asset_id' => $assetId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new Asset(
            $row['asset_id'],
            $row['name'],
            $row['device_type'],
            (bool)$row['is_warranty'],
            $row['status']
        );
    }

    /**
     * อัปเดตสถานะของอุปกรณ์ (เช่น Normal / Repairing / Damaged)
     */
    public function updateAssetStatus(string $assetId, string $status): bool {
        $validStatuses = ['Normal', 'Repairing', 'Damaged'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE assets SET status = :status WHERE asset_id = :asset_id");
        return $stmt->execute([
            ':status' => $status,
            ':asset_id' => $assetId
        ]);
    }

    /**
     * ดึงรายการอุปกรณ์ IT ทั้งหมดในองค์กร
     */
    public function getAllAssets(): array {
        $stmt = $this->pdo->query("SELECT * FROM assets ORDER BY asset_id ASC");
        $rows = $stmt->fetchAll();
        $assets = [];

        foreach ($rows as $row) {
            $assets[] = new Asset(
                $row['asset_id'],
                $row['name'],
                $row['device_type'],
                (bool)$row['is_warranty'],
                $row['status']
            );
        }

        return $assets;
    }

    /**
     * เพิ่มอุปกรณ์ IT ใหม่เข้าสู่ระบบ
     */
    public function addAsset(string $assetId, string $name, string $deviceType, bool $isWarranty): bool {
        $stmt = $this->pdo->prepare("INSERT INTO assets (asset_id, name, device_type, is_warranty, status) VALUES (:asset_id, :name, :device_type, :is_warranty, 'Normal')");
        return $stmt->execute([
            ':asset_id' => $assetId,
            ':name' => $name,
            ':device_type' => $deviceType,
            ':is_warranty' => $isWarranty ? 1 : 0
        ]);
    }
}
