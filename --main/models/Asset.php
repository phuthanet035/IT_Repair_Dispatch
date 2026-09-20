<?php
/**
 * Model Class: Asset
 * รับผิดชอบโดย: คนที่ 1 (IT Asset Manager)
 * เป็น Class Data Model แทนข้อมูลอุปกรณ์ IT 1 ชิ้น
 */

class Asset {
    private string $assetId;
    private string $name;
    private string $deviceType; // Computer, Printer, Monitor, Network, Other
    private bool $isWarranty;   // true = ในประกัน, false = หมดประกัน
    private string $status;     // Normal, Repairing, Damaged

    public function __construct(
        string $assetId,
        string $name,
        string $deviceType,
        bool $isWarranty = true,
        string $status = 'Normal'
    ) {
        $this->assetId = $assetId;
        $this->name = $name;
        $this->deviceType = $deviceType;
        $this->isWarranty = $isWarranty;
        $this->status = $status;
    }

    // Getters & Setters
    public function getAssetId(): string {
        return $this->assetId;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDeviceType(): string {
        return $this->deviceType;
    }

    public function isWarranty(): bool {
        return $this->isWarranty;
    }

    public function getWarrantyStatusText(): string {
        return $this->isWarranty ? 'ในประกัน (In Warranty)' : 'หมดประกัน (Out of Warranty)';
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function setStatus(string $status): void {
        $validStatuses = ['Normal', 'Repairing', 'Damaged'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }
    }

    public function toArray(): array {
        return [
            'asset_id' => $this->assetId,
            'name' => $this->name,
            'device_type' => $this->deviceType,
            'is_warranty' => $this->isWarranty,
            'warranty_text' => $this->getWarrantyStatusText(),
            'status' => $this->status
        ];
    }
}
