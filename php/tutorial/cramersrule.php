<?php

/**
 * 克莱姆法则求解线性方程组（使用 equations 格式）
 * 每个方程格式: [a, b, c, ..., d] 表示 a*x + b*y + c*z + ... = d
 * 
 * @param array $equations 方程数组（必须是方阵，方程个数 = 未知数个数）
 * @return array 解向量 [x, y, z, ...]
 * @throws Exception 如果矩阵奇异（行列式为零）或方程个数与未知数个数不匹配
 */
function CramersRule($equations)
{
    if (empty($equations)) {
        throw new Exception("方程列表为空");
    }
    
    $n = count($equations);           // 方程个数
    $dim = count($equations[0]) - 1;  // 未知数个数
    
    if ($n != $dim) {
        throw new Exception("方程个数($n)与未知数个数($dim)不匹配，不是方阵");
    }
    
    // 提取系数矩阵
    $matrix = [];
    $constants = [];
    for ($i = 0; $i < $n; $i++) {
        $matrix[] = array_slice($equations[$i], 0, $dim);
        $constants[] = $equations[$i][$dim];
    }
    
    // 计算系数矩阵的行列式 D
    $detD = determinant($matrix);
    if (abs($detD) < 1e-12) {
        throw new Exception("矩阵奇异，行列式为零，无法使用克莱姆法则");
    }
    
    // 求解每个未知数
    $solution = [];
    for ($i = 0; $i < $dim; $i++) {
        // 替换第 i 列为常数向量
        $modifiedMatrix = $matrix;
        for ($j = 0; $j < $n; $j++) {
            $modifiedMatrix[$j][$i] = $constants[$j];
        }
        
        // 计算替换后的行列式
        $detDi = determinant($modifiedMatrix);
        
        // 计算解
        $solution[$i] = $detDi / $detD;
    }
    
    return $solution;
}

/**
 * 计算矩阵的行列式（递归法，支持任意维度）
 * 
 * @param array $matrix n×n 矩阵
 * @return float 行列式的值
 * @throws Exception 如果不是方阵
 */
function determinant($matrix)
{
    $n = count($matrix);
    
    // 检查是否为方阵
    foreach ($matrix as $row) {
        if (count($row) != $n) {
            throw new Exception("矩阵不是方阵，无法计算行列式");
        }
    }
    
    // 1x1 矩阵
    if ($n == 1) {
        return $matrix[0][0];
    }
    
    // 2x2 矩阵
    if ($n == 2) {
        return $matrix[0][0] * $matrix[1][1] - $matrix[0][1] * $matrix[1][0];
    }
    
    // 3x3 矩阵（使用对角线法则，效率更高）
    if ($n == 3) {
        return $matrix[0][0] * $matrix[1][1] * $matrix[2][2]
             + $matrix[0][1] * $matrix[1][2] * $matrix[2][0]
             + $matrix[0][2] * $matrix[1][0] * $matrix[2][1]
             - $matrix[0][2] * $matrix[1][1] * $matrix[2][0]
             - $matrix[0][1] * $matrix[1][0] * $matrix[2][2]
             - $matrix[0][0] * $matrix[1][2] * $matrix[2][1];
    }
    
    // 更高维度：使用拉普拉斯展开（递归）
    $det = 0;
    for ($col = 0; $col < $n; $col++) {
        // 计算代数余子式
        $subMatrix = [];
        for ($i = 1; $i < $n; $i++) {
            $subRow = [];
            for ($j = 0; $j < $n; $j++) {
                if ($j != $col) {
                    $subRow[] = $matrix[$i][$j];
                }
            }
            $subMatrix[] = $subRow;
        }
        
        $sign = ($col % 2 == 0) ? 1 : -1;
        $det += $sign * $matrix[0][$col] * determinant($subMatrix);
    }
    
    return $det;
}

?>
