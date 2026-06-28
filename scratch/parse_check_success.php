<?php
$path = 'C:\Users\HP\.gemini\antigravity-ide\brain\26a373cf-b43a-42e9-a5c5-bf297b2a2bd3\.system_generated\steps\172\content.md';
$content = file_get_contents($path);
$jsonStart = strpos($content, '{');
if ($jsonStart !== false) {
    $content = substr($content, $jsonStart);
}
$data = json_decode($content, true);

function searchCheck($item) {
    if (isset($item['item'])) {
        foreach ($item['item'] as $subItem) {
            searchCheck($subItem);
        }
    } else {
        $url = $item['request']['url'];
        if (is_array($url)) {
            $url = $url['raw'] ?? '';
        }
        if (strpos($url, 'sms/check') !== false) {
            echo "Name: " . $item['name'] . "\n";
            echo "URL: " . ($item['request']['method'] ?? 'GET') . " " . $url . "\n";
            if (isset($item['response'])) {
                foreach ($item['response'] as $idx => $resp) {
                    echo "Response #" . $idx . " Name: " . ($resp['name'] ?? '') . "\n";
                    echo "Response Body:\n" . ($resp['body'] ?? '') . "\n";
                    echo "=========================================\n";
                }
            }
        }
    }
}

searchCheck($data);
