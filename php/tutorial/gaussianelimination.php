<?php

/**
 * 高斯消元法求解线性方程组（使用 equations 格式）
 * 每个方程格式: [a, b, c, ..., d] 表示 a*x + b*y + c*z + ... = d
 * 
 * @param array $equations 方程数组（必须是方阵，方程个数 = 未知数个数）
 * @return array 解向量 [x, y, z, ...]
 * @throws Exception 如果矩阵奇异或方程个数与未知数个数不匹配
 */
function GaussianElimination($equations)
{
    if (empty($equations)) {
        throw new Exception("方程列表为空");
    }
    
    $n = count($equations);           // 方程个数
    $dim = count($equations[0]) - 1;  // 未知数个数
    
    if ($n != $dim) {
        throw new Exception("方程个数($n)与未知数个数($dim)不匹配，不是方阵");
    }
    
    // 构建增广矩阵（从 equations 格式转换）
    $aug = [];
    for ($i = 0; $i < $n; $i++) {
        // 每一行：系数部分 + 常数项
        $coeffs = array_slice($equations[$i], 0, $dim);
        $const = $equations[$i][$dim];
        $aug[$i] = array_merge($coeffs, [$const]);
    }
    
    // 前向消元（列主元高斯消元）
    for ($i = 0; $i < $n; $i++) {
        // 1. 寻找主元（列主元，提高数值稳定性）
        $maxRow = $i;
        for ($k = $i + 1; $k < $n; $k++) {
            if (abs($aug[$k][$i]) > abs($aug[$maxRow][$i])) {
                $maxRow = $k;
            }
        }
        
        // 2. 交换行
        if ($maxRow != $i) {
            $temp = $aug[$i];
            $aug[$i] = $aug[$maxRow];
            $aug[$maxRow] = $temp;
        }
        
        // 3. 检查主元是否为零（矩阵奇异）
        if (abs($aug[$i][$i]) < 1e-12) {
            throw new Exception("矩阵奇异，无法求解（主元为零）");
        }
        
        // 4. 消去下方行
        for ($k = $i + 1; $k < $n; $k++) {
            $factor = $aug[$k][$i] / $aug[$i][$i];
            for ($j = $i; $j < $n + 1; $j++) {
                $aug[$k][$j] -= $factor * $aug[$i][$j];
            }
        }
    }
    
    // 回代求解
    $solution = array_fill(0, $n, 0);
    for ($i = $n - 1; $i >= 0; $i--) {
        $sum = 0;
        for ($j = $i + 1; $j < $n; $j++) {
            $sum += $aug[$i][$j] * $solution[$j];
        }
        $solution[$i] = ($aug[$i][$n] - $sum) / $aug[$i][$i];
    }
    
    return $solution;
}

/**
 * 求解超定线性方程组（通用版）
 * 每个方程格式: [a, b, c, ... d] 表示 a*x + b*y + c*z + ... = d
 * 
 * @param array $equations 方程数组
 * @return array 解向量 [x, y, z, ...]
 */
function SolveOverdetermined($equations)
{
    if (empty($equations)) {
        throw new Exception("方程列表为空");
    }
    
    $dim = count($equations[0]) - 1;  // 未知数个数 = 系数个数 - 1
    $n = count($equations);
    
    if ($n < $dim) {
        throw new Exception("方程个数($n)少于未知数个数($dim)");
    }
    
    // 初始化法方程的累加矩阵和向量
    $matrix = array_fill(0, $dim, array_fill(0, $dim, 0));
    $vector = array_fill(0, $dim, 0);
    
    // 累加：构建 M^T * M 和 M^T * b
    foreach ($equations as $eq) {
        $coeffs = array_slice($eq, 0, $dim);  // [a, b, c, ...]
        $const = $eq[$dim];                    // d
        
        // 累加矩阵
        for ($i = 0; $i < $dim; $i++) {
            for ($j = 0; $j < $dim; $j++) {
                $matrix[$i][$j] += $coeffs[$i] * $coeffs[$j];
            }
            // 累加向量
            $vector[$i] += $coeffs[$i] * $const;
        }
    }
    
    // 构建法方程（现在是方阵了）
    $normalEquations = [];
    for ($i = 0; $i < $dim; $i++) {
        $row = array_merge($matrix[$i], [$vector[$i]]);
        $normalEquations[] = $row;
    }
    
    // 调用高斯消元法求解
    return GaussianElimination($normalEquations);
}

?>
