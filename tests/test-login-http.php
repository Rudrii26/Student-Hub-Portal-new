<?php
$ch = curl_init('http://localhost/Student%20Hub%20Portal/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'studentId' => 'priya2026',
    'password'  => 'Charusat@Pass2026',
    'isAjax'    => '1'
]));
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $code\n";
echo "Response Body: $res\n";
