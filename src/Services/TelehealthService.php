<?php

namespace App\Services;

/**
 * Telehealth Service
 * Integrates with Jitsi Meet for Tele-pharmacy
 */
class TelehealthService
{
    private $jitsiDomain = 'https://meet.jit.si/';

    /**
     * Generate a secure video room
     * 
     * @param string $hn Patient HN
     * @param string $type 'consult' or 'followup'
     * @return array Room details
     */
    public function createConsultationRoom($hn, $type = 'consult')
    {
        // Generate a unique, unguessable room name
        // Format: Drugmuk-Consult-{HN}-{Random}-{Timestamp}
        $random = bin2hex(random_bytes(4));
        $timestamp = time();
        $roomName = "Drugmuk-{$type}-{$hn}-{$random}-{$timestamp}";
        
        $roomUrl = $this->jitsiDomain . $roomName;

        // In a production app with paid Jitsi/Zoom, we would generate JWT tokens here
        // For Jitsi public/testing, the URL is sufficient but open.
        // We can add config settings to password protect if needed later.

        return [
            'room_name' => $roomName,
            'url' => $roomUrl,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600) // 1 hour
        ];
    }
}
