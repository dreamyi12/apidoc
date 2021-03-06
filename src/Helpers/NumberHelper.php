<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/2/24
 * Time: 15:32
 * Desc: 数值助手类
 */


namespace Dreamyi12\ApiDoc\Helpers;


/**
 * Class NumberHelper
 * @package Dreamyi12\ApiDoc\Helpers
 */
class NumberHelper {

    /**
     * 格式化文件比特大小
     * @param int $size 文件大小(比特)
     * @param int $dec 小数位
     * @param string $delimiter 数字和单位间的分隔符
     * @return string
     */
    public static function formatBytes(int $size, int $dec = 2, string $delimiter = ''): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $size >= 1024 && $i < 5; $i++) {
            $size /= 1024;
        }

        return round($size, $dec) . $delimiter . ($units[$i] ?? 'Unknown');
    }


    /**
     * 值是否在某范围内
     * @param int|float $val 值
     * @param int|float $min 小值
     * @param int|float $max 大值
     * @return bool
     */
    public static function inRange($val, $min, $max): bool {
        $val = floatval($val);
        $min = floatval($min);
        $max = floatval($max);
        return $val >= $min && $val <= $max;
    }


    /**
     * 对数列求和,忽略非数值.
     * @param mixed ...$vals
     * @return float
     */
    public static function sum(...$vals): float {
        $res = 0;
        foreach ($vals as $val) {
            if (is_numeric($val)) {
                $res += floatval($val);
            }
        }

        return $res;
    }


    /**
     * 对数列求平均值,忽略非数值.
     * @param mixed ...$vals
     * @return float
     */
    public static function average(...$vals): float {
        $res   = 0;
        $count = 0;
        $total = 0;
        foreach ($vals as $val) {
            if (is_numeric($val)) {
                $total += floatval($val);
                $count++;
            }
        }

        if ($count > 0) {
            $res = $total / $count;
        }

        return $res;
    }


    /**
     * 获取地理距离/米.
     * 参数分别为两点的经度和纬度.lat:-90~90,lng:-180~180.
     * @param float $lng1 起点经度
     * @param float $lat1 起点纬度
     * @param float $lng2 终点经度
     * @param float $lat2 终点纬度
     * @return float
     */
    public static function geoDistance(float $lng1 = 0, float $lat1 = 0, float $lng2 = 0, float $lat2 = 0): float {
        $earthRadius = 6371000.0;
        $lat1        = ($lat1 * pi()) / 180;
        $lng1        = ($lng1 * pi()) / 180;
        $lat2        = ($lat2 * pi()) / 180;
        $lng2        = ($lng2 * pi()) / 180;

        $calcLongitude = $lng2 - $lng1;
        $calcLatitude  = $lat2 - $lat1;
        $stepOne       = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo       = 2 * asin(min(1, sqrt($stepOne)));
        $res           = $earthRadius * $stepTwo;

        return $res;
    }


    /**
     * 数值格式化(会四舍五入)
     * @param float|int|string $number 要格式化的数字
     * @param int $decimals 小数位数
     * @return string
     */
    public static function numberFormat($number, int $decimals = 2): string {
        return number_format(floatval($number), $decimals, '.', '');
    }


    /**
     * 数值截取(不会四舍五入)
     * @param float|int|string $number 要格式化的数字
     * @param int $decimals 小数位数
     * @return float
     */
    public static function numberSub($number, int $decimals = 2): float {
        if ($decimals == 0 && ValidateHelper::isInteger($number)) {
            return floatval($number);
        }

        return intval(floatval($number) * pow(10, $decimals)) / pow(10, $decimals);
    }


    /**
     * 生成随机浮点数
     * @param float $min 小值
     * @param float $max 大值
     * @return float
     */
    public static function randFloat(float $min = 0, float $max = 1): float {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }


    /**
     * 将金额转为大写人民币
     * @param float $num 金额,元(最大支持千亿)
     * @param int $decimals 精确小数位数(最大支持为3,即厘)
     * @return string
     */
    public static function money2Yuan(float $num, int $decimals = 0): string {
        $int = intval($num);
        if (strlen($int) > 12) {
            throw new \Exception('The maximum value supports 12 bits!');
        }

        $uppers = '零壹贰叁肆伍陆柒捌玖';
        $units  = '元拾佰仟万拾佰仟亿拾佰仟';

        if ($decimals > 0) {
            $decimals = min($decimals, 3);
            $adds     = ['角', '分', '厘'];
            $num      = $num * pow(10, $decimals);

            for ($i = 0; $i < $decimals; $i++) {
                $units = $adds[$i] . $units;
            }
        }

        $res = '';
        $i   = 0;
        while (true) {
            if ($i == 0) {
                $n = substr($num, strlen($num) - 1, 1);
            } else {
                $n = $num % 10;
            }
            $p1 = substr($uppers, 3 * $n, 3);
            $p2 = substr($units, 3 * $i, 3);

            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $res = $p1 . $p2 . $res;
            } else {
                $res = $p1 . $res;
            }

            $i   = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            }
        }

        $j   = 0;
        $len = strlen($res);
        while ($j < $len) {
            $m = substr($res, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left  = substr($res, 0, $j);
                $right = substr($res, $j + 3);
                $res   = $left . $right;
                $j     = $j - 3;
                $len   = $len - 3;
            }
            $j = $j + 3;
        }

        if (substr($res, strlen($res) - 3, 3) == '零') {
            $res = substr($res, 0, strlen($res) - 3);
        }

        if (empty($res)) {
            return "零元整";
        } else {
            $res .= "整";
        }

        return $res;
    }


    /**
     * 求以 $base 为底 $num 的对数临近值
     * @param mixed $num 非负数
     * @param int $base 底数
     * @param bool $left 是否向左取整
     * @return int
     * @throws
     */
    public static function nearLogarithm($num, int $base = 2, bool $left = true): int {
        if (!is_numeric($num) || $num < 0) {
            throw new \Exception('The $num must be non-negative!');
        } elseif ($base <= 0) {
            throw new \Exception('The $base must be a positive integer!');
        }

        $log = log($num, $base);

        return $left ? intval($log) : intval(ceil($log));
    }


    /**
     * 将自然数按底数进行拆解
     * @param int $num 自然数
     * @param int $base 底数
     * @return array
     * @throws
     */
    public static function splitNaturalNum(int $num, int $base): array {
        if (!ValidateHelper::isNaturalNum($num)) {
            throw new \Exception('The $num must be a natural number!');
        } elseif ($base <= 0) {
            throw new \Exception('The $base must be a positive integer!');
        }

        $res = [];
        while ($num > $base) {
            $n     = self::nearLogarithm($num, $base, true);
            $child = pow($base, $n);
            $num   -= $child;
            array_push($res, $child);
        }

        if ($num > 0 || ($num == 0 && empty($res))) {
            array_push($res, $num);
        }

        return $res;
    }


}