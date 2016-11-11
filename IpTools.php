<?php

/**
 * Class IpTools
 *
 * GMP extension (--with-gmp)
 * apt-get install php5-gmp
 */
class IpTools {

    /**
     * Convert mask to prefix subnet
     * @param string $mask
     * @return int
     * @throws Exception
     */
    public static function ip4_mask_to_prefix($mask) {

        if (substr_count($mask, '.') < 3) {
            $mask .= str_repeat('.0', 3 - substr_count($mask, '.'));
        }

        if ( ! self::ip4_validate($mask)) {
            throw new Exception('Incorrect parameter mask');
        }

        $dec = (int)sprintf('%u', ip2long($mask));
        return substr_count(decbin($dec), '1');
    }


    /**
     * Convert prefix to mask subnet
     * @param int $prefix
     * @return int
     * @throws Exception
     */
    public static function ip4_prefix_to_mask($prefix) {

        if ($prefix < 1 || $prefix > 32) {
            throw new Exception('Incorrect parameter prefix, need int interval from 1 to 32');
        }

        $mask_bin  = str_repeat('1', $prefix);
        $mask_bin .= str_repeat('0', 32 - $prefix);

        return long2ip(bindec($mask_bin));
    }


    /**
     * Convert ip subnet to ip start and ip end
     * @param string     $addr
     * @param string|int $mask
     * @return array
     * @throws Exception
     */
    public static function ip4_to_range($addr, $mask = null) {

        if ( ! self::ip4_validate($addr)) {
            throw new Exception("Please supply a valid IPv4 address");
        }

        // If mask like 255.255.0.0
        if (strpos($mask, ".")) {
            $mask = self::ip4_mask_to_prefix($mask);
        }

        $result = array();

        // start ip
        $result[] = is_null($mask)
            ? $addr
            : self::int_to_ip4((int)sprintf('%u', ip2long($addr)) & (-1 << (32 - (int)$mask)));

        // end ip
        $result[] = is_null($mask)
            ? $addr
            : self::int_to_ip4((int)sprintf('%u', ip2long($addr)) + pow(2, (32 - (int)$mask)) - 1);

        return $result;
    }


    /**
     * Validate ipv4 address
     * @param string $addr
     * @return bool
     */
    public static function ip4_validate($addr) {

        return filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }


    /**
     * Convert ipv4 to int
     * @param string $addr
     * @return int
     * @throws Exception
     */
    public static function ip4_to_int($addr) {

        if ( ! self::ip4_validate($addr)) {
            throw new Exception("Please supply a valid IPv4 address");
        }
        return (int)sprintf('%u', ip2long($addr));
    }


    /**
     * Convert int to ipv4
     * @param int $int_addr
     * @return string
     */
    public static function int_to_ip4($int_addr) {
        return long2ip($int_addr);
    }


    /**
     * Expand an IPv6 Address
     * This will take an IPv6 address written in short form and expand it to include all zeros.
     * @param  string  $addr A valid IPv6 address
     * @return string  The expanded notation IPv6 address
     * @throws Exception
     */
    public static function ip6_expand($addr) {

        if ( ! self::ip6_validate($addr)) {
            throw new Exception("Please supply a valid IPv6 address");
        }

        /* Check if there are segments missing, insert if necessary */
        if (strpos($addr, '::') !== false) {
            $part    = explode('::', $addr);
            $part[0] = explode(':', $part[0]);
            $part[1] = explode(':', $part[1]);
            $missing = array();

            for ($i = 0; $i < (8 - (count($part[0]) + count($part[1]))); $i++)
                array_push($missing, '0000');

            $missing = array_merge($part[0], $missing);
            $part    = array_merge($missing, $part[1]);
        } else {
            $part = explode(":", $addr);
        }

        /* Pad each segment until it has 4 digits */
        foreach ($part as &$p) {
            while (strlen($p) < 4) $p = '0' . $p;
        }
        unset($p);

        /* Join segments */
        $result = implode(':', $part);

        /* Quick check to make sure the length is as expected */
        if (strlen($result) == 39) {
            return $result;
        } else {
            throw new Exception("Error expand ipv6, length not equal 39");
        }
    }


    /**
     * Compress an IPv6 Address
     * This will take an IPv6 address and rewrite it in short form.
     * @param  string  $addr A valid IPv6 address
     * @return string  The address in short form notation
     */
    public static function ip6_compress($addr) {
        /* PHP provides a shortcut for this operation */
        $result = inet_ntop(inet_pton($addr));
        return $result;
    }


    /**
     * Generate an IPv6 mask from prefix notation
     * This will convert a prefix to an IPv6 address mask (used for IPv6 math)
     * @param  integer $prefix The prefix size, an integer between 1 and 127 (inclusive)
     * @return string  The IPv6 mask address for the prefix size
     */
    public static function ip6_prefix_to_mask($prefix) {

        /* Make sure the prefix is a number between 1 and 127 (inclusive) */
        $prefix = intval($prefix);
        if ($prefix < 0 || $prefix > 128) return false;

        $mask = '0b';
        for ($i = 0; $i < $prefix; $i++)             $mask .= '1';
        for ($i = strlen($mask) - 2; $i < 128; $i++) $mask .= '0';

        $mask   = gmp_strval(gmp_init($mask), 16);
        $result = '';
        for ($i = 0; $i < 8; $i++) {
            $result .= substr($mask, $i * 4, 4);
            if ($i != 7) $result .= ':';
        }

        return self::ip6_compress($result);
    }


    /**
     * Generate an IPv6 prefix from mask notation
     * This will convert a mask to an IPv6 address prefix
     * @param  integer $mask The IPv6 mask address for the prefix size
     * @return string  The prefix size, an integer between 1 and 127 (inclusive)
     * @throws Exception
     */
    public static function ip6_mask_to_prefix($mask) {

        if ( ! self::ip6_validate($mask)) {
            throw new Exception("Please supply a valid IPv6 address");
        }
        $mask = self::ip6_expand($mask);

        $binNum = '';
        foreach (unpack('C*', inet_pton($mask)) as $byte) {
            $binNum .= str_pad(decbin($byte), 8, "0", STR_PAD_LEFT);
        }

        return substr_count($binNum, '1');
    }


    /**
     * Convert an IPv6 address and prefix size to an address range for the network.
     * This will take an IPv6 address and prefix and return the first and last address available for the network.
     * @param  string     $addr A valid IPv6 address
     * @param  int|string $mask The prefix size, an integer between 1 and 127 (inclusive)
     * @return array An array with two strings containing the start and end address for the IPv6 network
     */
    public static function ip6_to_range($addr, $mask) {

        if (is_numeric($mask)) {
            $prefix = $mask;
            $mask   = self::ip6_prefix_to_mask($mask);
        } else {
            $prefix = self::ip6_mask_to_prefix($mask);
        }

        $size   = 128 - $prefix;
        $addr   = gmp_init('0x' . str_replace(':', '', self::ip6_expand($addr)));
        $mask   = gmp_init('0x' . str_replace(':', '', self::ip6_expand($mask)));
        $prefix = gmp_and($addr, $mask);
        $start  = gmp_strval(gmp_add($prefix, '0x1'), 16);
        $end    = '0b';

        for ($i = 0; $i < $size; $i++) $end .= '1';
        $end = gmp_strval(gmp_add($prefix, gmp_init($end)), 16);

        $start_result = '';
        for ($i = 0; $i < 8; $i++) {
            $start_result .= substr($start, $i * 4, 4);
            if ($i != 7) $start_result .= ':';
        }

        $end_result = '';
        for ($i = 0; $i < 8; $i++) {
            $end_result .= substr($end, $i * 4, 4);
            if ($i != 7) $end_result .= ':';
        }

        $result = array(
            self::ip6_compress($start_result),
            self::ip6_compress($end_result)
        );
        return $result;
    }


    /**
     * Validate ipv6 address
     * @param string $addr
     * @return bool
     * @throws Exception
     */
    public static function ip6_validate($addr) {

        return filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }


    /**
     * Convert ipv6 to numeric
     * @param string $addr A human readable IPv6 address.
     * @return string Decimal number, written out as a string due to limits on the size of int and float.
     * @throws Exception
     */
    public static function ip6_to_numeric($addr) {

        if ( ! self::ip6_validate($addr)) {
            throw new Exception("Please supply a valid IPv6 address");
        }

        if (strlen($addr) != 39) {
            $addr = self::ip6_expand($addr);
        }

        $binNum = '';
        foreach (unpack('C*', inet_pton($addr)) as $byte) {
            $binNum .= str_pad(decbin($byte), 8, "0", STR_PAD_LEFT);
        }
        return base_convert(ltrim($binNum, '0'), 2, 10);
    }


    /**
     * Convert ipv6 to bin
     * @param string $addr A human readable IPv6 address.
     * @return string
     * @throws Exception
     */
    public static function ip6_to_bin($addr) {

        if ( ! self::ip6_validate($addr)) {
            throw new Exception("Please supply a valid IPv6 address");
        }

        if (strlen($addr) != 39) {
            $addr = self::ip6_expand($addr);
        }

        return inet_pton($addr);
    }


    /**
     * Convert an IPv6 address to two 64-bit integers.
     * This will translate an IPv6 address into two 64-bit integer values for storage in an SQL database.
     * @param  string  $addr A valid IPv6 address
     * @return array   An array with two strings containing the 64-bit interger values
     */
    public static function ip6_to_split_int($addr) {

        /* Expand the address if necessary */
        if (strlen($addr) != 39) {
            $addr = self::ip6_expand($addr);
            if ($addr == false) return false;
        }

        $addr = str_replace(':', '', $addr);
        $p1   = '0x' . substr($addr, 0, 16);
        $p2   = '0x' . substr($addr, 16);
        $p1   = gmp_init($p1);
        $p2   = gmp_init($p2);

        $result = array(gmp_strval($p1), gmp_strval($p2));
        return $result;
    }


    /**
     * Convert two 64-bit integer values into an IPv6 address
     * This will translate an array of 64-bit integer values back into an IPv6 address
     * @param  array  $val An array containing two strings representing 64-bit integer values
     * @return string An IPv6 address
     */
    public static function split_int_to_ip6($val) {

        /* Make sure input is an array with 2 numerical strings */
        $result = false;
        if ( ! is_array($val) || count($val) != 2) return $result;

        $p1 = gmp_strval(gmp_init($val[0]), 16);
        $p2 = gmp_strval(gmp_init($val[1]), 16);
        while (strlen($p1) < 16) $p1 = '0' . $p1;
        while (strlen($p2) < 16) $p2 = '0' . $p2;

        $addr = $p1 . $p2;
        for ($i = 0; $i < 8; $i++) {
            $result .= substr($addr, $i * 4, 4);
            if ($i != 7) $result .= ':';
        }

        return self::ip6_compress($result);
    }


    /**
     * Convert numeric to ipv6
     * @param string $dec Decimal number, written out as a string due to limits on the size of int and float.
     * @return string A human readable IPv6 address.
     * @throws Exception
     */
    public static function numeric_to_ip6($dec) {

        if (function_exists('gmp_init')) {
            $bin = gmp_strval(gmp_init($dec, 10), 2);

        } elseif (function_exists('bcadd')) {
            $bin = '';
            do {
                $bin = bcmod($dec, '2') . $bin;
                $dec = bcdiv($dec, '2', 0);
            } while (bccomp($dec, '0'));

        } else {
            throw new Exception('Error convert numeric to ipv6, GMP or BCMATH extension not installed!');
        }

        $bin = str_pad($bin, 128, '0', STR_PAD_LEFT);
        $ip  = array();

        for ($bit = 0; $bit <= 7; $bit++) {
            $bin_part = substr($bin, $bit * 16, 16);
            $ip[]     = dechex(bindec($bin_part));
        }
        $ip = implode(':', $ip);
        return inet_ntop(inet_pton($ip));
    }


    /**
     * Convert bin to ipv6
     * @param string $bin
     * @return string|bool
     */
    public static function bin_to_ip6($bin) {
        return inet_ntop($bin);
    }
}