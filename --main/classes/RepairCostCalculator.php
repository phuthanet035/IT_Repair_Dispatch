<?php
/**
 * Class 2 (คนที่ 2): RepairCostCalculator — คำนวณประเมินค่าซ่อมและระยะเวลา
 * หน้าที่: คิดราคาค่าซ่อมเบื้องต้น และคำนวณระยะเวลาแก้ไขตามระดับความสำคัญ (Priority)
 * ฟังก์ชันหลัก:
 * 1. calculateCost(deviceType, issueSeverity, isWarranty): คำนวณค่าอะไหล่ + ค่าบริการ (ถ้าอยู่ในประกันคิด 0 บาท / หมดประกันคิดตามจริง)
 * 2. estimateCompletionTime(priority): ประเมินเวลาที่ใช้ซ่อม (เช่น ด่วนมาก = 2 ชั่วโมง, อาการทั่วไป = 24 ชั่วโมง)
 */

class RepairCostCalculator {

    /**
     * คำนวณค่าซ่อมเบื้องต้น (ค่าอะไหล่ + ค่าบริการ)
     * - ในประกัน (isWarranty = true) => 0.00 บาท
     * - หมดประกัน (isWarranty = false) => คิดตามประเภทอุปกรณ์ + ระดับความรุนแรง
     */
    public function calculateCost(string $deviceType, string $issueSeverity, bool $isWarranty): float {
        // หากยังอยู่ในประกัน ฟรีค่าซ่อมและค่าอะไหล่ 100%
        if ($isWarranty) {
            return 0.00;
        }

        // ค่าบริการพื้นฐาน (Base Service Fee) แยกตามประเภทอุปกรณ์
        $baseServiceFees = [
            'Computer' => 350.00,
            'Printer'  => 300.00,
            'Monitor'  => 250.00,
            'Network'  => 400.00,
            'Other'    => 200.00
        ];

        // ประเมินค่าอะไหล่เบื้องต้นตามระดับความรุนแรงของอาการเสีย (Severity)
        $severityPartCosts = [
            'Low'      => 150.00,  // อาการเล็กน้อย เช่น สายหลุด, ลงไดรเวอร์
            'Medium'   => 500.00,  // อาการปานกลาง เช่น เปลีย่น RAM, หัวพิมพ์ตัน
            'High'     => 1200.00, // อาการหนัก เช่น จอแตก, เมนบอร์ดชำรุด
            'Critical' => 2500.00  // ชำรุดวิกฤต ต้องเปลี่ยนชิ้นส่วนหลัก
        ];

        $serviceFee = $baseServiceFees[$deviceType] ?? 300.00;
        $partCost   = $severityPartCosts[$issueSeverity] ?? 500.00;

        return $serviceFee + $partCost;
    }

    /**
     * ประเมินระยะเวลาแก้ไขตามระดับความสำคัญ (Priority)
     * - Urgent (ด่วนมาก/กระทบงานหลัก) => 2 ชั่วโมง
     * - High (สูง) => 6 ชั่วโมง
     * - Normal (ปกติ/อาการทั่วไป) => 24 ชั่วโมง
     * - Low (ต่ำ) => 48 ชั่วโมง
     */
    public function estimateCompletionTime(string $priority): string {
        switch (strtoupper($priority)) {
            case 'URGENT':
            case 'ด่วนมาก':
                return '2 ชั่วโมง (Urgent Service)';

            case 'HIGH':
            case 'สูง':
                return '6 ชั่วโมง (High Priority)';

            case 'LOW':
            case 'ต่ำ':
                return '48 ชั่วโมง (Low Priority)';

            case 'NORMAL':
            case 'ปกติ':
            default:
                return '24 ชั่วโมง (Standard Repair)';
        }
    }
}
