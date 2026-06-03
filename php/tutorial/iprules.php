<?php

/**
 * 根据IP地址类别获取默认子网掩码
 * 
 * @param string $ip IP地址
 * @return string 子网掩码
 */
function getDefaultMaskByClass($ip) {
    $first_octet = (int) explode('.', $ip)[0];
    
    if ($first_octet >= 1 && $first_octet <= 126) {
        // A类地址
        return '255.0.0.0';
    } elseif ($first_octet >= 128 && $first_octet <= 191) {
        // B类地址
        return '255.255.0.0';
    } elseif ($first_octet >= 192 && $first_octet <= 223) {
        // C类地址
        return '255.255.255.0';
    } elseif ($first_octet >= 224 && $first_octet <= 239) {
        // D类（组播），通常不用于子网判断
        return null;
    } elseif ($first_octet >= 240 && $first_octet <= 255) {
        // E类（保留），通常不用于子网判断
        return null;
    } else {
        return null;
    }
}

/**
 * 判断IP是否在子网内（自动根据IP类别选择默认掩码）
 * 
 * @param string $ip 要检查的IP地址
 * @param string $subnet_ip 子网网络IP（如：203.10.99.27）
 * @param string|null $custom_mask 自定义掩码（可选，如果不提供则自动根据$subnet_ip的类别选择）
 * @return bool 是否在子网内
 */
function isIpInSubnetAuto($ip, $subnet_ip, $custom_mask = null) {
    // 验证IP地址格式
    if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet_ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    // 确定子网掩码
    if ($custom_mask !== null) {
        $mask = $custom_mask;
        if (!filter_var($mask, FILTER_VALIDATE_IP)) {
            return false;
        }
    } else {
        $mask = getDefaultMaskByClass($subnet_ip);
        if ($mask === null) {
            return false;
        }
    }
    
    // 转换为长整数进行位运算
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet_ip);
    $mask_long = ip2long($mask);
    
    if ($ip_long === false || $subnet_long === false || $mask_long === false) {
        return false;
    }
    
    // 计算网络地址并比较
    $subnet_network = $subnet_long & $mask_long;
    $ip_network = $ip_long & $mask_long;
    
    return $subnet_network == $ip_network;
}

/**
 * 获取子网信息（网络地址、广播地址、IP范围等）
 * 
 * @param string $subnet_ip 子网中的任意IP
 * @param string|null $custom_mask 自定义掩码（可选）
 * @return array|null 子网信息
 */
function getSubnetInfoAuto($subnet_ip, $custom_mask = null) {
    if (!filter_var($subnet_ip, FILTER_VALIDATE_IP)) {
        return null;
    }
    
    // 确定子网掩码
    if ($custom_mask !== null) {
        $mask = $custom_mask;
        if (!filter_var($mask, FILTER_VALIDATE_IP)) {
            return null;
        }
    } else {
        $mask = getDefaultMaskByClass($subnet_ip);
        if ($mask === null) {
            return null;
        }
    }
    
    $subnet_long = ip2long($subnet_ip);
    $mask_long = ip2long($mask);
    
    if ($subnet_long === false || $mask_long === false) {
        return null;
    }
    
    // 计算网络地址和广播地址
    $network_long = $subnet_long & $mask_long;
    $broadcast_long = $network_long | (~$mask_long);
    
    // 获取IP类别
    $first_octet = (int) explode('.', $subnet_ip)[0];
    $ip_class = '';
    if ($first_octet >= 1 && $first_octet <= 126) $ip_class = 'A';
    elseif ($first_octet >= 128 && $first_octet <= 191) $ip_class = 'B';
    elseif ($first_octet >= 192 && $first_octet <= 223) $ip_class = 'C';
    elseif ($first_octet >= 224 && $first_octet <= 239) $ip_class = 'D (组播)';
    elseif ($first_octet >= 240 && $first_octet <= 255) $ip_class = 'E (保留)';
    
    return [
        'ip_class' => $ip_class,
        'subnet_mask' => $mask,
        'network_address' => long2ip($network_long),
        'broadcast_address' => long2ip($broadcast_long),
        'first_usable_ip' => long2ip($network_long + 1),
        'last_usable_ip' => long2ip($broadcast_long - 1),
        'total_ips' => $broadcast_long - $network_long + 1,
        'usable_ips' => $broadcast_long - $network_long - 1
    ];
}

// ========== 使用示例 ==========
/*
echo "=== 示例1：C类地址 ===\n";
$subnet_ip = '203.10.99.27';
$check_ip = '203.10.99.100';
$result = isIpInSubnetAuto($check_ip, $subnet_ip);
echo "IP {$check_ip} 是否在子网 {$subnet_ip} 内？ " . ($result ? "是" : "否") . "\n";

$info = getSubnetInfoAuto($subnet_ip);
echo "子网信息：\n";
print_r($info);

echo "\n=== 示例2：B类地址 ===\n";
$subnet_ip_b = '172.16.35.22';
$check_ip_b = '172.16.88.99';
$result_b = isIpInSubnetAuto($check_ip_b, $subnet_ip_b);
echo "IP {$check_ip_b} 是否在子网 {$subnet_ip_b} 内？ " . ($result_b ? "是" : "否") . "\n";

$info_b = getSubnetInfoAuto($subnet_ip_b);
echo "子网信息：\n";
print_r($info_b);

echo "\n=== 示例3：A类地址 ===\n";
$subnet_ip_a = '10.5.33.88';
$check_ip_a = '10.9.22.11';
$result_a = isIpInSubnetAuto($check_ip_a, $subnet_ip_a);
echo "IP {$check_ip_a} 是否在子网 {$subnet_ip_a} 内？ " . ($result_a ? "是" : "否") . "\n";

$info_a = getSubnetInfoAuto($subnet_ip_a);
echo "子网信息：\n";
print_r($info_a);

echo "\n=== 示例4：使用自定义掩码覆盖默认规则 ===\n";
$custom_mask = '255.255.255.192'; // /26
$subnet_ip = '203.10.99.27';
$check_ip = '203.10.99.65';
$result_custom = isIpInSubnetAuto($check_ip, $subnet_ip, $custom_mask);
echo "使用掩码 {$custom_mask} 判断，IP {$check_ip} 是否在子网内？ " . ($result_custom ? "是" : "否") . "\n";
*/

?>
