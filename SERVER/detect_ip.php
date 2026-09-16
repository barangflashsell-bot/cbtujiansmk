<?php
/**
 * CBT Network IP Auto-Detector
 * Dynamically detects active IPv4 addresses on the current machine and network.
 */
$ips = @gethostbynamel(gethostname()) ?: [];
$valid = [];

if (is_array($ips)) {
    foreach ($ips as $ip) {
        if ($ip !== '127.0.0.1' && !str_starts_with($ip, '169.254.') && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $valid[] = $ip;
        }
    }
}

if (empty($valid)) {
    $valid[] = '192.168.1.11';
}

echo implode(',', $valid);
