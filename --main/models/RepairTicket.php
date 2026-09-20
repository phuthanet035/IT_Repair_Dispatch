<?php
/**
 * Model Class: RepairTicket
 * Shared Data Model สำหรับเก็บข้อมูลตั๋วแจ้งซ่อม 1 ใบ (รวมถึงอีเมลผู้แจ้ง รูปก่อน/หลังซ่อม ช่างที่รับงาน)
 */

class RepairTicket {
    private string $ticketId;
    private string $userName;
    private string $department;
    private string $phone;
    private ?string $userEmail;
    private string $assetId;
    private string $deviceType;
    private string $issueDescription;
    private string $issueSeverity; // Low, Medium, High, Critical
    private string $priority;      // Low, Normal, High, Urgent
    private float $estimatedCost;
    private string $estimatedCompletionTime;
    private string $status;        // Pending, In Progress, Completed
    private ?string $issueImage;   // พาธรูปถ่ายอาการเสีย (ก่อนซ่อม)
    private ?string $completionImage; // พาธรูปถ่ายเมื่อซ่อมเสร็จ (หลังซ่อม)
    private ?string $assignedTechnician; // ช่างผู้รับงาน
    private string $createdAt;

    public function __construct(
        string $ticketId,
        string $userName,
        string $department,
        string $phone,
        string $assetId,
        string $deviceType,
        string $issueDescription,
        string $issueSeverity = 'Medium',
        string $priority = 'Normal',
        float $estimatedCost = 0.0,
        string $estimatedCompletionTime = '24 ชั่วโมง',
        string $status = 'Pending',
        ?string $userEmail = null,
        ?string $issueImage = null,
        ?string $completionImage = null,
        ?string $assignedTechnician = null,
        ?string $createdAt = null
    ) {
        $this->ticketId = $ticketId;
        $this->userName = $userName;
        $this->department = $department;
        $this->phone = $phone;
        $this->assetId = $assetId;
        $this->deviceType = $deviceType;
        $this->issueDescription = $issueDescription;
        $this->issueSeverity = $issueSeverity;
        $this->priority = $priority;
        $this->estimatedCost = $estimatedCost;
        $this->estimatedCompletionTime = $estimatedCompletionTime;
        $this->status = $status;
        $this->userEmail = $userEmail;
        $this->issueImage = $issueImage;
        $this->completionImage = $completionImage;
        $this->assignedTechnician = $assignedTechnician;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    // Getters & Setters
    public function getTicketId(): string { return $this->ticketId; }
    public function getUserName(): string { return $this->userName; }
    public function getDepartment(): string { return $this->department; }
    public function getPhone(): string { return $this->phone; }
    public function getUserEmail(): ?string { return $this->userEmail; }
    public function getAssetId(): string { return $this->assetId; }
    public function getDeviceType(): string { return $this->deviceType; }
    public function getIssueDescription(): string { return $this->issueDescription; }
    public function getIssueSeverity(): string { return $this->issueSeverity; }
    public function getPriority(): string { return $this->priority; }
    public function getEstimatedCost(): float { return $this->estimatedCost; }
    public function getEstimatedCompletionTime(): string { return $this->estimatedCompletionTime; }
    public function getStatus(): string { return $this->status; }
    public function getIssueImage(): ?string { return $this->issueImage; }
    public function getCompletionImage(): ?string { return $this->completionImage; }
    public function getAssignedTechnician(): ?string { return $this->assignedTechnician; }
    public function getCreatedAt(): string { return $this->createdAt; }

    public function setStatus(string $status): void {
        $validStatuses = ['Pending', 'In Progress', 'Completed'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }
    }

    public function setCompletionImage(?string $completionImage): void {
        $this->completionImage = $completionImage;
    }

    public function setAssignedTechnician(?string $assignedTechnician): void {
        $this->assignedTechnician = $assignedTechnician;
    }

    public function toArray(): array {
        return [
            'ticket_id' => $this->ticketId,
            'user_name' => $this->userName,
            'department' => $this->department,
            'phone' => $this->phone,
            'user_email' => $this->userEmail,
            'asset_id' => $this->assetId,
            'device_type' => $this->deviceType,
            'issue_description' => $this->issueDescription,
            'issue_severity' => $this->issueSeverity,
            'priority' => $this->priority,
            'estimated_cost' => $this->estimatedCost,
            'formatted_cost' => number_format($this->estimatedCost, 2) . ' บาท',
            'estimated_completion_time' => $this->estimatedCompletionTime,
            'status' => $this->status,
            'issue_image' => $this->issueImage,
            'completion_image' => $this->completionImage,
            'assigned_technician' => $this->assignedTechnician,
            'created_at' => $this->createdAt
        ];
    }
}
