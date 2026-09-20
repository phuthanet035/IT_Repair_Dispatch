<?php
/**
 * Email Notification Service (EmailNotifier.php)
 * หน้าที่: ส่งอีเมลแจ้งเตือนใบแจ้งซ่อมใหม่ และ ส่งอีเมลแจ้งเตือนเมื่อซ่อมเสร็จแล้วไปยังอีเมลผู้แจ้ง
 * รองรับ:
 * 1. Brevo REST API v3 (https://api.brevo.com/v3/smtp/email) - แนะนำสำหรับ Localhost/XAMPP
 * 2. SMTP (Brevo SMTP / Gmail / Outlook / Custom SMTP)
 * 3. Local Simulation Log (storage/email_logs.txt)
 */

require_once __DIR__ . '/../models/RepairTicket.php';

class EmailNotifier {
    private string $adminEmail;
    private array $mailConfig;

    public function __construct(string $adminEmail = 'it-support@company.com') {
        $this->adminEmail = $adminEmail;
        
        $configFile = __DIR__ . '/../config/mail.php';
        if (file_exists($configFile)) {
            $this->mailConfig = include $configFile;
        } else {
            $this->mailConfig = [
                'driver' => 'brevo_api',
                'smtp_active' => false
            ];
        }
    }

    /**
     * ส่งอีเมลแจ้งเตือนเมื่อมีใบแจ้งซ่อมใหม่ (New Repair Ticket Email)
     */
    public function sendTicketCreatedNotification(RepairTicket $ticket, string $userEmail = ''): bool {
        $subject = "=?UTF-8?B?" . base64_encode("🔔 แจ้งเตือนใบแจ้งซ่อมอุปกรณ์ IT ใหม่ [{$ticket->getTicketId()}]") . "?=";
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px; }
                .header { background: #4F46E5; color: #fff; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px; background: #fafafa; }
                .ticket-box { background: #EEF2FF; border-left: 4px solid #4F46E5; padding: 12px; margin: 15px 0; font-size: 18px; font-weight: bold; color: #3730A3; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .info-table td { padding: 8px; border-bottom: 1px solid #eee; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>ระบบแจ้งซ่อมอุปกรณ์ IT (IT Repair System)</h2>
                </div>
                <div class='content'>
                    <h3>มีใบแจ้งซ่อมอุปกรณ์ใหม่เข้าสู่ระบบ!</h3>
                    <div class='ticket-box'>รหัสใบแจ้งซ่อม (Ticket ID): {$ticket->getTicketId()}</div>
                    
                    <table class='info-table'>
                        <tr><td><strong>ผู้แจ้งซ่อม:</strong></td><td>{$ticket->getUserName()}</td></tr>
                        <tr><td><strong>แผนก / ฝ่าย (ตำแหน่ง):</strong></td><td>{$ticket->getDepartment()}</td></tr>
                        <tr><td><strong>เบอร์โทรศัพท์:</strong></td><td>{$ticket->getPhone()}</td></tr>
                        <tr><td><strong>อีเมลผู้แจ้ง:</strong></td><td>" . ($userEmail ?: '-') . "</td></tr>
                        <tr><td><strong>รหัสอุปกรณ์ (Asset ID):</strong></td><td>{$ticket->getAssetId()} ({$ticket->getDeviceType()})</td></tr>
                        <tr><td><strong>อาการเสีย:</strong></td><td>{$ticket->getIssueDescription()}</td></tr>
                        <tr><td><strong>ราคาประเมินค่าซ่อม:</strong></td><td><strong style='color:#059669;'>{$ticket->toArray()['formatted_cost']}</strong></td></tr>
                        <tr><td><strong>เวลาประเมินแก้ไขเสร็จ:</strong></td><td><strong style='color:#4F46E5;'>{$ticket->getEstimatedCompletionTime()}</strong></td></tr>
                        <tr><td><strong>สถานะปัจจุบัน:</strong></td><td><span style='background:#FEF3C7; color:#92400E; padding:3px 8px; border-radius:4px;'>{$ticket->getStatus()}</span></td></tr>
                    </table>
                </div>
                <div class='footer'>
                    <p>ข้อความนี้ถูกส่งโดยอัตโนมัติจากระบบแจ้งซ่อมอุปกรณ์ IT</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // ส่งหา Admin
        $this->dispatchEmail($this->adminEmail, $subject, $body, "CREATED Ticket {$ticket->getTicketId()} (Admin)");

        // ส่งหา User
        if (!empty($userEmail)) {
            $this->dispatchEmail($userEmail, $subject, $body, "CREATED Ticket {$ticket->getTicketId()} (User: $userEmail)");
        }

        return true;
    }

    /**
     * ส่งอีเมลแจ้งเตือนเมื่อซ่อมเสร็จแล้ว (Repair Completed Notification Email)
     */
    public function sendCompletionNotification(RepairTicket $ticket, string $userEmail): bool {
        if (empty($userEmail)) {
            return false;
        }

        $assignedTech = $ticket->getAssignedTechnician() ?: 'ทีมงานช่าง IT Support';
        $subject = "=?UTF-8?B?" . base64_encode("🎉 แจ้งเตือน: อุปกรณ์ IT ของคุณซ่อมเสร็จเรียบร้อยแล้ว! [{$ticket->getTicketId()}]") . "?=";
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                .header { background: linear-gradient(135deg, #059669 0%, #10B981 100%); color: #fff; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                .header h2 { margin: 0; font-size: 22px; font-weight: 700; }
                .content { padding: 24px; background: #ffffff; }
                .status-badge { display: inline-block; background: #D1FAE5; color: #065F46; font-weight: bold; padding: 6px 16px; border-radius: 20px; font-size: 14px; margin-bottom: 15px; }
                .ticket-box { background: #ECFDF5; border-left: 5px solid #10B981; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .ticket-id { font-size: 20px; font-weight: bold; color: #047857; margin-bottom: 4px; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .info-table td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }
                .label { color: #64748B; width: 40%; font-weight: 500; }
                .val { color: #0F172A; width: 60%; }
                .footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 13px; color: #94A3B8; }
                .btn { display: inline-block; background: #059669; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🛠️ ระบบแจ้งซ่อมอุปกรณ์ IT (IT Repair System)</h2>
                </div>
                <div class='content'>
                    <div style='text-align: center;'>
                        <span class='status-badge'>✅ สถานะ: ซ่อมเสร็จเรียบร้อยแล้ว (Completed)</span>
                    </div>
                    <h3 style='color:#059669; margin-top: 5px;'>เรียนคุณ {$ticket->getUserName()}</h3>
                    <p>ทางทีมงาน IT Support ขอแจ้งให้ทราบว่า การดำเนินการซ่อมแซมอุปกรณ์ตามคำร้องแจ้งซ่อมของคุณ ได้เสร็จสิ้นสมบูรณ์เรียบร้อยแล้ว ท่านสามารถติดต่อขอรับอุปกรณ์คืนหรือเข้าใช้งานได้ทันที</p>
                    
                    <div class='ticket-box'>
                        <div class='ticket-id'>รหัสใบแจ้งซ่อม: {$ticket->getTicketId()}</div>
                        <div style='color:#047857; font-size:14px;'>อุปกรณ์: {$ticket->getAssetId()} ({$ticket->getDeviceType()})</div>
                    </div>
                    
                    <table class='info-table'>
                        <tr><td class='label'><strong>ผู้แจ้งซ่อม:</strong></td><td class='val'>{$ticket->getUserName()} ({$ticket->getDepartment()})</td></tr>
                        <tr><td class='label'><strong>อาการเสียที่แจ้ง:</strong></td><td class='val'>{$ticket->getIssueDescription()}</td></tr>
                        <tr><td class='label'><strong>ช่างผู้รับผิดชอบ:</strong></td><td class='val'><strong>{$assignedTech}</strong></td></tr>
                        <tr><td class='label'><strong>ค่าบริการ / ค่าซ่อมจริง:</strong></td><td class='val'><strong style='color:#059669; font-size: 16px;'>{$ticket->toArray()['formatted_cost']}</strong></td></tr>
                        <tr><td class='label'><strong>เวลาดำเนินการเสร็จ:</strong></td><td class='val'>" . date('d/m/Y H:i น.') . "</td></tr>
                    </table>

                    <div style='text-align: center; margin-top: 20px;'>
                        <p style='font-size: 14px; color: #475569;'>หากมีข้อสงสัยเพิ่มเติม สามารถติดต่อแผนก IT Support ได้ทันที</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติจากระบบ IT Repair System</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->dispatchEmail($userEmail, $subject, $body, "COMPLETED Ticket {$ticket->getTicketId()} (User: $userEmail)");
    }

    /**
     * ส่งอีเมลแจ้งเตือนช่างเมื่อได้รับการมอบหมายงาน (Job Assigned Notification to Technician)
     */
    public function sendJobAssignedNotification(RepairTicket $ticket, string $technicianEmail, string $technicianName): bool {
        if (empty($technicianEmail)) {
            return false;
        }

        $priorityColors = [
            'Urgent' => '#EF4444',
            'High' => '#F97316',
            'Normal' => '#3B82F6',
            'Low' => '#10B981'
        ];
        $pColor = $priorityColors[$ticket->getPriority()] ?? '#3B82F6';

        $subject = "=?UTF-8?B?" . base64_encode("👨‍🔧 มอบหมายงานซ่อมใหม่ให้คุณ: [{$ticket->getTicketId()}] ({$ticket->getPriority()})") . "?=";

        $body = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                .header { background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); color: #fff; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                .header h2 { margin: 0; font-size: 22px; font-weight: 700; }
                .content { padding: 24px; background: #ffffff; }
                .ticket-box { background: #EEF2FF; border-left: 5px solid #6366F1; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .ticket-id { font-size: 20px; font-weight: bold; color: #3730A3; margin-bottom: 4px; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .info-table td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }
                .label { color: #64748B; width: 40%; font-weight: 500; }
                .val { color: #0F172A; width: 60%; }
                .footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9; font-size: 13px; color: #94A3B8; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🛠️ ระบบจัดการและแจกงานซ่อม IT</h2>
                </div>
                <div class='content'>
                    <h3 style='color:#4338CA; margin-top: 5px;'>เรียนคุณ {$technicianName}</h3>
                    <p>ผู้ดูแลระบบได้มอบหมายงานแจ้งซ่อมอุปกรณ์ IT รายการใหม่ให้ท่านเป็นผู้ดูแลรับผิดชอบ กรุณาตรวจสอบรายละเอียดและดำเนินการซ่อมแซมตามลำดับความสำคัญ</p>
                    
                    <div class='ticket-box'>
                        <div class='ticket-id'>รหัสงานซ่อม: {$ticket->getTicketId()}</div>
                        <div style='color:#4F46E5; font-size:14px;'>อุปกรณ์: {$ticket->getAssetId()} ({$ticket->getDeviceType()})</div>
                        <div style='margin-top:6px;'><span style='background:{$pColor}; color:#fff; font-size:12px; font-weight:bold; padding:3px 10px; border-radius:12px;'>ความเร่งด่วน: {$ticket->getPriority()}</span></div>
                    </div>
                    
                    <table class='info-table'>
                        <tr><td class='label'><strong>ผู้แจ้งซ่อม:</strong></td><td class='val'>{$ticket->getUserName()} ({$ticket->getDepartment()})</td></tr>
                        <tr><td class='label'><strong>เบอร์ติดต่อ:</strong></td><td class='val'>{$ticket->getPhone()}</td></tr>
                        <tr><td class='label'><strong>อาการเสีย:</strong></td><td class='val'>{$ticket->getIssueDescription()}</td></tr>
                        <tr><td class='label'><strong>กำหนดเวลาประเมิน:</strong></td><td class='val'>{$ticket->getEstimatedCompletionTime()}</td></tr>
                        <tr><td class='label'><strong>สถานะปัจจุบัน:</strong></td><td class='val'><span style='background:#DBEAFE; color:#1E40AF; padding:2px 8px; border-radius:4px; font-weight:bold;'>กำลังดำเนินการ (In Progress)</span></td></tr>
                    </table>
                    
                    <p style='margin-top:20px; font-size:14px; color:#64748B;'>ท่านสามารถเข้าสู่ระบบเพื่ออัปเดตความคืบหน้า หรือบันทึกรายงานซ่อมเสร็จได้ที่หน้าแดชบอร์ดงานซ่อม</p>
                </div>
                <div class='footer'>
                    <p>ข้อความนี้ถูกส่งโดยอัตโนมัติจากระบบแจกงานและบริการ IT</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->dispatchEmail($technicianEmail, $subject, $body, "JOB ASSIGNED to $technicianName ($technicianEmail)");
    }

    /**
     * ส่งอีเมลทดสอบระบบ (Test Email Dispatch)
     */
    public function sendTestEmail(string $targetEmail): array {
        if (empty($targetEmail)) {
            return ['success' => false, 'message' => 'กรุณาระบุอีเมลปลายทางที่ต้องการทดสอบ'];
        }

        $subject = "=?UTF-8?B?" . base64_encode("🧪 ทดสอบการส่งอีเมลระบบแจ้งซ่อม IT (Brevo / Email Test)") . "?=";
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
            <div style='max-width: 500px; margin: 0 auto; border: 1px solid #4F46E5; border-radius: 8px; padding: 20px;'>
                <h3 style='color: #4F46E5; margin-top: 0;'>🎉 ทดสอบการส่งอีเมลสำเร็จ!</h3>
                <p>อีเมลฉบับนี้เป็นการทดสอบการเชื่อมต่อระบบส่งอีเมล (Brevo API / SMTP) จากระบบแจ้งซ่อมอุปกรณ์ IT</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 13px; color: #666;'>เวลาที่ทดสอบ: " . date('Y-m-d H:i:s') . "</p>
            </div>
        </body>
        </html>
        ";

        $success = $this->dispatchEmail($targetEmail, $subject, $body, "TEST EMAIL to $targetEmail");
        if ($success) {
            return ['success' => true, 'message' => "ส่งอีเมลทดสอบไปยัง [$targetEmail] สำเร็จเรียบร้อยแล้ว!"];
        }
        return ['success' => false, 'message' => "ส่งอีเมลไม่สำเร็จ กรุณาตรวจสอบการตั้งค่าและ Log"];
    }

    /**
     * เลือกว่าจะส่งผ่าน Brevo API, SMTP หรือ Local Log ตามคอนฟิก
     */
    public function dispatchEmail(string $to, string $subject, string $body, string $logContext): bool {
        $driver = $this->mailConfig['driver'] ?? 'brevo_api';
        $smtpActive = !empty($this->mailConfig['smtp_active']);

        // หากเปิดใช้งานส่งจริง (smtp_active == true)
        if ($smtpActive) {
            $brevoKey = trim($this->mailConfig['brevo_api_key'] ?? '');
            if (($driver === 'brevo_api' || !empty($brevoKey)) && !empty($brevoKey)) {
                $sent = $this->sendBrevoApi($to, $subject, $body);
                if ($sent) {
                    $this->logEmail("SUCCESS via Brevo REST API: $logContext to $to");
                    return true;
                } else {
                    $this->logEmail("FAILED via Brevo API, trying SMTP fallback...: $logContext to $to");
                }
            }

            $smtpUser = trim($this->mailConfig['username'] ?? '');
            if (($driver === 'smtp' || !empty($smtpUser)) && !empty($smtpUser)) {
                $sent = $this->sendSmtp($to, $subject, $body, $this->mailConfig);
                if ($sent) {
                    $this->logEmail("SUCCESS via SMTP: $logContext to $to");
                    return true;
                } else {
                    $this->logEmail("FAILED via SMTP: $logContext to $to");
                }
            }
        }

        // Local Simulation Log: ไม่เรียก @mail() เพื่อป้องกัน Timeout 30-60 วินาทีบน Windows/XAMPP
        $this->logEmail("LOGGED (Local Sim): $logContext to $to");
        return true;
    }

    /**
     * ส่งอีเมลผ่าน Brevo REST API v3 (POST https://api.brevo.com/v3/smtp/email)
     */
    private function sendBrevoApi(string $to, string $subject, string $body): bool {
        $apiKey = trim($this->mailConfig['brevo_api_key'] ?? '');
        if (empty($apiKey)) {
            $this->logEmail("Brevo API Error: API Key is empty in config/mail.php");
            return false;
        }

        $fromEmail = !empty($this->mailConfig['from_email']) ? $this->mailConfig['from_email'] : 'no-reply@company.com';
        $fromName  = !empty($this->mailConfig['from_name']) ? $this->mailConfig['from_name'] : 'ระบบแจ้งซ่อมอุปกรณ์ IT';

        // Decode UTF-8 MIME base64 subject if encoded
        $cleanSubject = $subject;
        if (preg_match('/=\?UTF-8\?B\?(.*?)\?=/i', $subject, $matches)) {
            $cleanSubject = base64_decode($matches[1]);
        }

        $payload = [
            'sender' => [
                'name'  => $fromName,
                'email' => $fromEmail
            ],
            'to' => [
                [
                    'email' => $to
                ]
            ],
            'subject'     => $cleanSubject,
            'htmlContent' => $body
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $apiKey,
                'accept: application/json',
                'content-type: application/json'
            ],
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->logEmail("Brevo cURL Error: $curlErr");
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $this->logEmail("Brevo API Success (HTTP $httpCode): Response = $response");
            return true;
        }

        $this->logEmail("Brevo API Failed (HTTP $httpCode): Response = $response");
        return false;
    }

    /**
     * ส่งอีเมลผ่าน SMTP Socket (รองรับ Brevo SMTP / Gmail / Outlook)
     */
    private function sendSmtp(string $to, string $subject, string $body, array $smtpConfig): bool {
        $host = $smtpConfig['host'] ?? 'smtp-relay.brevo.com';
        $port = (int)($smtpConfig['port'] ?? 587);
        $user = trim($smtpConfig['username'] ?? '');
        $pass = trim($smtpConfig['password'] ?? '');
        $from = $smtpConfig['from_email'] ?? $user;
        $fromName = $smtpConfig['from_name'] ?? 'ระบบแจ้งซ่อมอุปกรณ์ IT';

        if (empty($user) || empty($pass)) {
            $this->logEmail("SMTP Error: Username or Password not set in config/mail.php");
            return false;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @stream_socket_client("$host:$port", $errno, $errstr, 2, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            $this->logEmail("SMTP Connection Failed to $host:$port - Error: $errstr ($errno)");
            return false;
        }
        stream_set_timeout($socket, 2);

        $getResponse = function($s) {
            $res = '';
            while ($str = @fgets($s, 512)) {
                $res .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $res;
        };

        $getResponse($socket);

        fputs($socket, "EHLO " . gethostname() . "\r\n");
        $getResponse($socket);

        fputs($socket, "AUTH LOGIN\r\n");
        $getResponse($socket);

        fputs($socket, base64_encode($user) . "\r\n");
        $getResponse($socket);

        fputs($socket, base64_encode(str_replace(' ', '', $pass)) . "\r\n");
        $authRes = $getResponse($socket);

        if (strpos($authRes, '235') === false) {
            $this->logEmail("SMTP Auth Failed: " . trim($authRes));
            fclose($socket);
            return false;
        }

        fputs($socket, "MAIL FROM: <$from>\r\n");
        $getResponse($socket);

        fputs($socket, "RCPT TO: <$to>\r\n");
        $getResponse($socket);

        fputs($socket, "DATA\r\n");
        $getResponse($socket);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";

        fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $dataRes = $getResponse($socket);

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return strpos($dataRes, '250') !== false;
    }

    private function logEmail(string $message): void {
        $logDir = __DIR__ . '/../storage';
        if (!file_exists($logDir)) @mkdir($logDir, 0777, true);
        $logMessage = "[" . date('Y-m-d H:i:s') . "] $message\n";
        @file_put_contents($logDir . '/email_logs.txt', $logMessage, FILE_APPEND);
    }
}
