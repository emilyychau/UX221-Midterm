<?php
 if(!defined('GETID3_LIBXML_OPTIONS') && defined('LIBXML_VERSION')) { if(LIBXML_VERSION >= 20621) { define('GETID3_LIBXML_OPTIONS', LIBXML_NOENT | LIBXML_NONET | LIBXML_NOWARNING | LIBXML_COMPACT); } else { define('GETID3_LIBXML_OPTIONS', LIBXML_NOENT | LIBXML_NONET | LIBXML_NOWARNING); } } class getid3_lib { public static function PrintHexBytes($string, $hex=true, $spaces=true, $htmlencoding='UTF-8') { $returnstring = ''; for ($i = 0; $i < strlen($string); $i++) { if ($hex) { $returnstring .= str_pad(dechex(ord($string[$i])), 2, '0', STR_PAD_LEFT); } else { $returnstring .= ' '.(preg_match("#[\x20-\x7E]#", $string[$i]) ? $string[$i] : '¤'); } if ($spaces) { $returnstring .= ' '; } } if (!empty($htmlencoding)) { if ($htmlencoding === true) { $htmlencoding = 'UTF-8'; } $returnstring = htmlentities($returnstring, ENT_QUOTES, $htmlencoding); } return $returnstring; } public static function trunc($floatnumber) { if ($floatnumber >= 1) { $truncatednumber = floor($floatnumber); } elseif ($floatnumber <= -1) { $truncatednumber = ceil($floatnumber); } else { $truncatednumber = 0; } if (self::intValueSupported($truncatednumber)) { $truncatednumber = (int) $truncatednumber; } return $truncatednumber; } public static function safe_inc(&$variable, $increment=1) { if (isset($variable)) { $variable += $increment; } else { $variable = $increment; } return true; } public static function CastAsInt($floatnum) { $floatnum = (float) $floatnum; if (self::trunc($floatnum) == $floatnum) { if (self::intValueSupported($floatnum)) { $floatnum = (int) $floatnum; } } return $floatnum; } public static function intValueSupported($num) { static $hasINT64 = null; if ($hasINT64 === null) { $hasINT64 = is_int(pow(2, 31)); if (!$hasINT64 && !defined('PHP_INT_MIN')) { define('PHP_INT_MIN', ~PHP_INT_MAX); } } if ($hasINT64 || (($num <= PHP_INT_MAX) && ($num >= PHP_INT_MIN))) { return true; } return false; } public static function SafeDiv($numerator, $denominator, $fallback = 0) { return $denominator ? $numerator / $denominator : $fallback; } public static function DecimalizeFraction($fraction) { list($numerator, $denominator) = explode('/', $fraction); return (int) $numerator / ($denominator ? $denominator : 1); } public static function DecimalBinary2Float($binarynumerator) { $numerator = self::Bin2Dec($binarynumerator); $denominator = self::Bin2Dec('1'.str_repeat('0', strlen($binarynumerator))); return ($numerator / $denominator); } public static function NormalizeBinaryPoint($binarypointnumber, $maxbits=52) { if (strpos($binarypointnumber, '.') === false) { $binarypointnumber = '0.'.$binarypointnumber; } elseif ($binarypointnumber[0] == '.') { $binarypointnumber = '0'.$binarypointnumber; } $exponent = 0; while (($binarypointnumber[0] != '1') || (substr($binarypointnumber, 1, 1) != '.')) { if (substr($binarypointnumber, 1, 1) == '.') { $exponent--; $binarypointnumber = substr($binarypointnumber, 2, 1).'.'.substr($binarypointnumber, 3); } else { $pointpos = strpos($binarypointnumber, '.'); $exponent += ($pointpos - 1); $binarypointnumber = str_replace('.', '', $binarypointnumber); $binarypointnumber = $binarypointnumber[0].'.'.substr($binarypointnumber, 1); } } $binarypointnumber = str_pad(substr($binarypointnumber, 0, $maxbits + 2), $maxbits + 2, '0', STR_PAD_RIGHT); return array('normalized'=>$binarypointnumber, 'exponent'=>(int) $exponent); } public static function Float2BinaryDecimal($floatvalue) { $maxbits = 128; $intpart = self::trunc($floatvalue); $floatpart = abs($floatvalue - $intpart); $pointbitstring = ''; while (($floatpart != 0) && (strlen($pointbitstring) < $maxbits)) { $floatpart *= 2; $pointbitstring .= (string) self::trunc($floatpart); $floatpart -= self::trunc($floatpart); } $binarypointnumber = decbin($intpart).'.'.$pointbitstring; return $binarypointnumber; } public static function Float2String($floatvalue, $bits) { $exponentbits = 0; $fractionbits = 0; switch ($bits) { case 32: $exponentbits = 8; $fractionbits = 23; break; case 64: $exponentbits = 11; $fractionbits = 52; break; default: return false; } if ($floatvalue >= 0) { $signbit = '0'; } else { $signbit = '1'; } $normalizedbinary = self::NormalizeBinaryPoint(self::Float2BinaryDecimal($floatvalue), $fractionbits); $biasedexponent = pow(2, $exponentbits - 1) - 1 + $normalizedbinary['exponent']; $exponentbitstring = str_pad(decbin($biasedexponent), $exponentbits, '0', STR_PAD_LEFT); $fractionbitstring = str_pad(substr($normalizedbinary['normalized'], 2), $fractionbits, '0', STR_PAD_RIGHT); return self::BigEndian2String(self::Bin2Dec($signbit.$exponentbitstring.$fractionbitstring), $bits % 8, false); } public static function LittleEndian2Float($byteword) { return self::BigEndian2Float(strrev($byteword)); } public static function BigEndian2Float($byteword) { $bitword = self::BigEndian2Bin($byteword); if (!$bitword) { return 0; } $signbit = $bitword[0]; $floatvalue = 0; $exponentbits = 0; $fractionbits = 0; switch (strlen($byteword) * 8) { case 32: $exponentbits = 8; $fractionbits = 23; break; case 64: $exponentbits = 11; $fractionbits = 52; break; case 80: $exponentstring = substr($bitword, 1, 15); $isnormalized = intval($bitword[16]); $fractionstring = substr($bitword, 17, 63); $exponent = pow(2, self::Bin2Dec($exponentstring) - 16383); $fraction = $isnormalized + self::DecimalBinary2Float($fractionstring); $floatvalue = $exponent * $fraction; if ($signbit == '1') { $floatvalue *= -1; } return $floatvalue; default: return false; } $exponentstring = substr($bitword, 1, $exponentbits); $fractionstring = substr($bitword, $exponentbits + 1, $fractionbits); $exponent = self::Bin2Dec($exponentstring); $fraction = self::Bin2Dec($fractionstring); if (($exponent == (pow(2, $exponentbits) - 1)) && ($fraction != 0)) { $floatvalue = NAN; } elseif (($exponent == (pow(2, $exponentbits) - 1)) && ($fraction == 0)) { if ($signbit == '1') { $floatvalue = -INF; } else { $floatvalue = INF; } } elseif (($exponent == 0) && ($fraction == 0)) { if ($signbit == '1') { $floatvalue = -0.0; } else { $floatvalue = 0.0; } } elseif (($exponent == 0) && ($fraction != 0)) { $floatvalue = pow(2, (-1 * (pow(2, $exponentbits - 1) - 2))) * self::DecimalBinary2Float($fractionstring); if ($signbit == '1') { $floatvalue *= -1; } } elseif ($exponent != 0) { $floatvalue = pow(2, ($exponent - (pow(2, $exponentbits - 1) - 1))) * (1 + self::DecimalBinary2Float($fractionstring)); if ($signbit == '1') { $floatvalue *= -1; } } return (float) $floatvalue; } public static function BigEndian2Int($byteword, $synchsafe=false, $signed=false) { $intvalue = 0; $bytewordlen = strlen($byteword); if ($bytewordlen == 0) { return false; } for ($i = 0; $i < $bytewordlen; $i++) { if ($synchsafe) { $intvalue += (ord($byteword[$i]) & 0x7F) * pow(2, ($bytewordlen - 1 - $i) * 7); } else { $intvalue += ord($byteword[$i]) * pow(256, ($bytewordlen - 1 - $i)); } } if ($signed && !$synchsafe) { if ($bytewordlen <= PHP_INT_SIZE) { $signMaskBit = 0x80 << (8 * ($bytewordlen - 1)); if ($intvalue & $signMaskBit) { $intvalue = 0 - ($intvalue & ($signMaskBit - 1)); } } else { throw new Exception('ERROR: Cannot have signed integers larger than '.(8 * PHP_INT_SIZE).'-bits ('.strlen($byteword).') in self::BigEndian2Int()'); } } return self::CastAsInt($intvalue); } public static function LittleEndian2Int($byteword, $signed=false) { return self::BigEndian2Int(strrev($byteword), false, $signed); } public static function LittleEndian2Bin($byteword) { return self::BigEndian2Bin(strrev($byteword)); } public static function BigEndian2Bin($byteword) { $binvalue = ''; $bytewordlen = strlen($byteword); for ($i = 0; $i < $bytewordlen; $i++) { $binvalue .= str_pad(decbin(ord($byteword[$i])), 8, '0', STR_PAD_LEFT); } return $binvalue; } public static function BigEndian2String($number, $minbytes=1, $synchsafe=false, $signed=false) { if ($number < 0) { throw new Exception('ERROR: self::BigEndian2String() does not support negative numbers'); } $maskbyte = (($synchsafe || $signed) ? 0x7F : 0xFF); $intstring = ''; if ($signed) { if ($minbytes > PHP_INT_SIZE) { throw new Exception('ERROR: Cannot have signed integers larger than '.(8 * PHP_INT_SIZE).'-bits in self::BigEndian2String()'); } $number = $number & (0x80 << (8 * ($minbytes - 1))); } while ($number != 0) { $quotient = ($number / ($maskbyte + 1)); $intstring = chr(ceil(($quotient - floor($quotient)) * $maskbyte)).$intstring; $number = floor($quotient); } return str_pad($intstring, $minbytes, "\x00", STR_PAD_LEFT); } public static function Dec2Bin($number) { if (!is_numeric($number)) { trigger_error('TypeError: Dec2Bin(): Argument #1 ($number) must be numeric, '.gettype($number).' given', E_USER_WARNING); return ''; } $bytes = array(); while ($number >= 256) { $bytes[] = (int) (($number / 256) - (floor($number / 256))) * 256; $number = floor($number / 256); } $bytes[] = (int) $number; $binstring = ''; foreach ($bytes as $i => $byte) { $binstring = (($i == count($bytes) - 1) ? decbin($byte) : str_pad(decbin($byte), 8, '0', STR_PAD_LEFT)).$binstring; } return $binstring; } public static function Bin2Dec($binstring, $signed=false) { $signmult = 1; if ($signed) { if ($binstring[0] == '1') { $signmult = -1; } $binstring = substr($binstring, 1); } $decvalue = 0; for ($i = 0; $i < strlen($binstring); $i++) { $decvalue += ((int) substr($binstring, strlen($binstring) - $i - 1, 1)) * pow(2, $i); } return self::CastAsInt($decvalue * $signmult); } public static function Bin2String($binstring) { $string = ''; $binstringreversed = strrev($binstring); for ($i = 0; $i < strlen($binstringreversed); $i += 8) { $string = chr(self::Bin2Dec(strrev(substr($binstringreversed, $i, 8)))).$string; } return $string; } public static function LittleEndian2String($number, $minbytes=1, $synchsafe=false) { $intstring = ''; while ($number > 0) { if ($synchsafe) { $intstring = $intstring.chr($number & 127); $number >>= 7; } else { $intstring = $intstring.chr($number & 255); $number >>= 8; } } return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT); } public static function array_merge_clobber($array1, $array2) { if (!is_array($array1) || !is_array($array2)) { return false; } $newarray = $array1; foreach ($array2 as $key => $val) { if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) { $newarray[$key] = self::array_merge_clobber($newarray[$key], $val); } else { $newarray[$key] = $val; } } return $newarray; } public static function array_merge_noclobber($array1, $array2) { if (!is_array($array1) || !is_array($array2)) { return false; } $newarray = $array1; foreach ($array2 as $key => $val) { if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) { $newarray[$key] = self::array_merge_noclobber($newarray[$key], $val); } elseif (!isset($newarray[$key])) { $newarray[$key] = $val; } } return $newarray; } public static function flipped_array_merge_noclobber($array1, $array2) { if (!is_array($array1) || !is_array($array2)) { return false; } $newarray = array_flip($array1); foreach (array_flip($array2) as $key => $val) { if (!isset($newarray[$key])) { $newarray[$key] = count($newarray); } } return array_flip($newarray); } public static function ksort_recursive(&$theArray) { ksort($theArray); foreach ($theArray as $key => $value) { if (is_array($value)) { self::ksort_recursive($theArray[$key]); } } return true; } public static function fileextension($filename, $numextensions=1) { if (strstr($filename, '.')) { $reversedfilename = strrev($filename); $offset = 0; for ($i = 0; $i < $numextensions; $i++) { $offset = strpos($reversedfilename, '.', $offset + 1); if ($offset === false) { return ''; } } return strrev(substr($reversedfilename, 0, $offset)); } return ''; } public static function PlaytimeString($seconds) { $sign = (($seconds < 0) ? '-' : ''); $seconds = round(abs($seconds)); $H = (int) floor( $seconds / 3600); $M = (int) floor(($seconds - (3600 * $H) ) / 60); $S = (int) round( $seconds - (3600 * $H) - (60 * $M) ); return $sign.($H ? $H.':' : '').($H ? str_pad($M, 2, '0', STR_PAD_LEFT) : intval($M)).':'.str_pad($S, 2, 0, STR_PAD_LEFT); } public static function DateMac2Unix($macdate) { return self::CastAsInt($macdate - 2082844800); } public static function FixedPoint8_8($rawdata) { return self::BigEndian2Int(substr($rawdata, 0, 1)) + (float) (self::BigEndian2Int(substr($rawdata, 1, 1)) / pow(2, 8)); } public static function FixedPoint16_16($rawdata) { return self::BigEndian2Int(substr($rawdata, 0, 2)) + (float) (self::BigEndian2Int(substr($rawdata, 2, 2)) / pow(2, 16)); } public static function FixedPoint2_30($rawdata) { $binarystring = self::BigEndian2Bin($rawdata); return self::Bin2Dec(substr($binarystring, 0, 2)) + (float) (self::Bin2Dec(substr($binarystring, 2, 30)) / pow(2, 30)); } public static function CreateDeepArray($ArrayPath, $Separator, $Value) { $ArrayPath = ltrim($ArrayPath, $Separator); $ReturnedArray = array(); if (($pos = strpos($ArrayPath, $Separator)) !== false) { $ReturnedArray[substr($ArrayPath, 0, $pos)] = self::CreateDeepArray(substr($ArrayPath, $pos + 1), $Separator, $Value); } else { $ReturnedArray[$ArrayPath] = $Value; } return $ReturnedArray; } public static function array_max($arraydata, $returnkey=false) { $maxvalue = false; $maxkey = false; foreach ($arraydata as $key => $value) { if (!is_array($value)) { if (($maxvalue === false) || ($value > $maxvalue)) { $maxvalue = $value; $maxkey = $key; } } } return ($returnkey ? $maxkey : $maxvalue); } public static function array_min($arraydata, $returnkey=false) { $minvalue = false; $minkey = false; foreach ($arraydata as $key => $value) { if (!is_array($value)) { if (($minvalue === false) || ($value < $minvalue)) { $minvalue = $value; $minkey = $key; } } } return ($returnkey ? $minkey : $minvalue); } public static function XML2array($XMLstring) { if (function_exists('simplexml_load_string') && function_exists('libxml_disable_entity_loader')) { $loader = @libxml_disable_entity_loader(true); $XMLobject = simplexml_load_string($XMLstring, 'SimpleXMLElement', GETID3_LIBXML_OPTIONS); $return = self::SimpleXMLelement2array($XMLobject); @libxml_disable_entity_loader($loader); return $return; } return false; } public static function SimpleXMLelement2array($XMLobject) { if (!is_object($XMLobject) && !is_array($XMLobject)) { return $XMLobject; } $XMLarray = $XMLobject instanceof SimpleXMLElement ? get_object_vars($XMLobject) : $XMLobject; foreach ($XMLarray as $key => $value) { $XMLarray[$key] = self::SimpleXMLelement2array($value); } return $XMLarray; } public static function hash_data($file, $offset, $end, $algorithm) { if (!self::intValueSupported($end)) { return false; } if (!in_array($algorithm, array('md5', 'sha1'))) { throw new getid3_exception('Invalid algorithm ('.$algorithm.') in self::hash_data()'); } $size = $end - $offset; $fp = fopen($file, 'rb'); fseek($fp, $offset); $ctx = hash_init($algorithm); while ($size > 0) { $buffer = fread($fp, min($size, getID3::FREAD_BUFFER_SIZE)); hash_update($ctx, $buffer); $size -= getID3::FREAD_BUFFER_SIZE; } $hash = hash_final($ctx); fclose($fp); return $hash; } public static function CopyFileParts($filename_source, $filename_dest, $offset, $length) { if (!self::intValueSupported($offset + $length)) { throw new Exception('cannot copy file portion, it extends beyond the '.round(PHP_INT_MAX / 1073741824).'GB limit'); } if (is_readable($filename_source) && is_file($filename_source) && ($fp_src = fopen($filename_source, 'rb'))) { if (($fp_dest = fopen($filename_dest, 'wb'))) { if (fseek($fp_src, $offset) == 0) { $byteslefttowrite = $length; while (($byteslefttowrite > 0) && ($buffer = fread($fp_src, min($byteslefttowrite, getID3::FREAD_BUFFER_SIZE)))) { $byteswritten = fwrite($fp_dest, $buffer, $byteslefttowrite); $byteslefttowrite -= $byteswritten; } fclose($fp_dest); return true; } else { fclose($fp_src); throw new Exception('failed to seek to offset '.$offset.' in '.$filename_source); } } else { throw new Exception('failed to create file for writing '.$filename_dest); } } else { throw new Exception('failed to open file for reading '.$filename_source); } } public static function iconv_fallback_int_utf8($charval) { if ($charval < 128) { $newcharstring = chr($charval); } elseif ($charval < 2048) { $newcharstring = chr(($charval >> 6) | 0xC0); $newcharstring .= chr(($charval & 0x3F) | 0x80); } elseif ($charval < 65536) { $newcharstring = chr(($charval >> 12) | 0xE0); $newcharstring .= chr(($charval >> 6) | 0xC0); $newcharstring .= chr(($charval & 0x3F) | 0x80); } else { $newcharstring = chr(($charval >> 18) | 0xF0); $newcharstring .= chr(($charval >> 12) | 0xC0); $newcharstring .= chr(($charval >> 6) | 0xC0); $newcharstring .= chr(($charval & 0x3F) | 0x80); } return $newcharstring; } public static function iconv_fallback_iso88591_utf8($string, $bom=false) { $newcharstring = ''; if ($bom) { $newcharstring .= "\xEF\xBB\xBF"; } for ($i = 0; $i < strlen($string); $i++) { $charval = ord($string[$i]); $newcharstring .= self::iconv_fallback_int_utf8($charval); } return $newcharstring; } public static function iconv_fallback_iso88591_utf16be($string, $bom=false) { $newcharstring = ''; if ($bom) { $newcharstring .= "\xFE\xFF"; } for ($i = 0; $i < strlen($string); $i++) { $newcharstring .= "\x00".$string[$i]; } return $newcharstring; } public static function iconv_fallback_iso88591_utf16le($string, $bom=false) { $newcharstring = ''; if ($bom) { $newcharstring .= "\xFF\xFE"; } for ($i = 0; $i < strlen($string); $i++) { $newcharstring .= $string[$i]."\x00"; } return $newcharstring; } public static function iconv_fallback_iso88591_utf16($string) { return self::iconv_fallback_iso88591_utf16le($string, true); } public static function iconv_fallback_utf8_iso88591($string) { $newcharstring = ''; $offset = 0; $stringlength = strlen($string); while ($offset < $stringlength) { if ((ord($string[$offset]) | 0x07) == 0xF7) { $charval = ((ord($string[($offset + 0)]) & 0x07) << 18) & ((ord($string[($offset + 1)]) & 0x3F) << 12) & ((ord($string[($offset + 2)]) & 0x3F) << 6) & (ord($string[($offset + 3)]) & 0x3F); $offset += 4; } elseif ((ord($string[$offset]) | 0x0F) == 0xEF) { $charval = ((ord($string[($offset + 0)]) & 0x0F) << 12) & ((ord($string[($offset + 1)]) & 0x3F) << 6) & (ord($string[($offset + 2)]) & 0x3F); $offset += 3; } elseif ((ord($string[$offset]) | 0x1F) == 0xDF) { $charval = ((ord($string[($offset + 0)]) & 0x1F) << 6) & (ord($string[($offset + 1)]) & 0x3F); $offset += 2; } elseif ((ord($string[$offset]) | 0x7F) == 0x7F) { $charval = ord($string[$offset]); $offset += 1; } else { $charval = false; $offset += 1; } if ($charval !== false) { $newcharstring .= (($charval < 256) ? chr($charval) : '?'); } } return $newcharstring; } public static function iconv_fallback_utf8_utf16be($string, $bom=false) { $newcharstring = ''; if ($bom) { $newcharstring .= "\xFE\xFF"; } $offset = 0; $stringlength = strlen($string); while ($offset < $stringlength) { if ((ord($string[$offset]) | 0x07) == 0xF7) { $charval = ((ord($string[($offset + 0)]) & 0x07) << 18) & ((ord($string[($offset + 1)]) & 0x3F) << 12) & ((ord($string[($offset + 2)]) & 0x3F) << 6) & (ord($string[($offset + 3)]) & 0x3F); $offset += 4; } elseif ((ord($string[$offset]) | 0x0F) == 0xEF) { $charval = ((ord($string[($offset + 0)]) & 0x0F) << 12) & ((ord($string[($offset + 1)]) & 0x3F) << 6) & (ord($string[($offset + 2)]) & 0x3F); $offset += 3; } elseif ((ord($string[$offset]) | 0x1F) == 0xDF) { $charval = ((ord($string[($offset + 0)]) & 0x1F) << 6) & (ord($string[($offset + 1)]) & 0x3F); $offset += 2; } elseif ((ord($string[$offset]) | 0x7F) == 0x7F) { $charval = ord($string[$offset]); $offset += 1; } else { $charval = false; $offset += 1; } if ($charval !== false) { $newcharstring .= (($charval < 65536) ? self::BigEndian2String($charval, 2) : "\x00".'?'); } } return $newcharstring; } public static function iconv_fallback_utf8_utf16le($string, $bom=false) { $newcharstring = ''; if ($bom) { $newcharstring .= "\xFF\xFE"; } $offset = 0; $stringlength = strlen($string); while ($offset < $stringlength) { if ((ord($string[$offset]) | 0x07) == 0xF7) { $charval = ((ord($string[($offset + 0)]) & 0x07) << 18) & ((ord($string[($offset + 1)]) & 0x3F) << 12) & ((ord($string[($offset + 2)]) & 0x3F) << 6) & (ord($string[($offset + 3)]) & 0x3F); $offset += 4; } elseif ((ord($string[$offset]) | 0x0F) == 0xEF) { $charval = ((ord($string[($offset + 0)]) & 0x0F) << 12) & ((ord($string[($offset + 1)]) & 0x3F) << 6) & (ord($string[($offset + 2)]) & 0x3F); $offset += 3; } elseif ((ord($string[$offset]) | 0x1F) == 0xDF) { $charval = ((ord($string[($offset + 0)]) & 0x1F) << 6) & (ord($string[($offset + 1)]) & 0x3F); $offset += 2; } elseif ((ord($string[$offset]) | 0x7F) == 0x7F) { $charval = ord($string[$offset]); $offset += 1; } else { $charval = false; $offset += 1; } if ($charval !== false) { $newcharstring .= (($charval < 65536) ? self::LittleEndian2String($charval, 2) : '?'."\x00"); } } return $newcharstring; } public static function iconv_fallback_utf8_utf16($string) { return self::iconv_fallback_utf8_utf16le($string, true); } public static function iconv_fallback_utf16be_utf8($string) { if (substr($string, 0, 2) == "\xFE\xFF") { $string = substr($string, 2); } $newcharstring = ''; for ($i = 0; $i < strlen($string); $i += 2) { $charval = self::BigEndian2Int(substr($string, $i, 2)); $newcharstring .= self::iconv_fallback_int_utf8($charval); } return $newcharstring; } public static function iconv_fallback_utf16le_utf8($string) { if (substr($string, 0, 2) == "\xFF\xFE") { $string = substr($string, 2); } $newcharstring = ''; for ($i = 0; $i < strlen($string); $i += 2) { $charval = self::LittleEndian2Int(substr($string, $i, 2)); $newcharstring .= self::iconv_fallback_int_utf8($charval); } return $newcharstring; } public static function iconv_fallback_utf16be_iso88591($string) { if (substr($string, 0, 2) == "\xFE\xFF") { $string = substr($string, 2); } $newcharstring = ''; for ($i = 0; $i < strlen($string); $i += 2) { $charval = self::BigEndian2Int(substr($string, $i, 2)); $newcharstring .= (($charval < 256) ? chr($charval) : '?'); } return $newcharstring; } public static function iconv_fallback_utf16le_iso88591($string) { if (substr($string, 0, 2) == "\xFF\xFE") { $string = substr($string, 2); } $newcharstring = ''; for ($i = 0; $i < strlen($string); $i += 2) { $charval = self::LittleEndian2Int(substr($string, $i, 2)); $newcharstring .= (($charval < 256) ? chr($charval) : '?'); } return $newcharstring; } public static function iconv_fallback_utf16_iso88591($string) { $bom = substr($string, 0, 2); if ($bom == "\xFE\xFF") { return self::iconv_fallback_utf16be_iso88591(substr($string, 2)); } elseif ($bom == "\xFF\xFE") { return self::iconv_fallback_utf16le_iso88591(substr($string, 2)); } return $string; } public static function iconv_fallback_utf16_utf8($string) { $bom = substr($string, 0, 2); if ($bom == "\xFE\xFF") { return self::iconv_fallback_utf16be_utf8(substr($string, 2)); } elseif ($bom == "\xFF\xFE") { return self::iconv_fallback_utf16le_utf8(substr($string, 2)); } return $string; } public static function iconv_fallback($in_charset, $out_charset, $string) { if ($in_charset == $out_charset) { return $string; } if (function_exists('mb_convert_encoding')) { if ((strtoupper($in_charset) == 'UTF-16') && (substr($string, 0, 2) != "\xFE\xFF") && (substr($string, 0, 2) != "\xFF\xFE")) { $string = "\xFF\xFE".$string; } if ((strtoupper($in_charset) == 'UTF-16') && (strtoupper($out_charset) == 'UTF-8')) { if (($string == "\xFF\xFE") || ($string == "\xFE\xFF")) { return ''; } } if ($converted_string = @mb_convert_encoding($string, $out_charset, $in_charset)) { switch ($out_charset) { case 'ISO-8859-1': $converted_string = rtrim($converted_string, "\x00"); break; } return $converted_string; } return $string; } elseif (function_exists('iconv')) { if ($converted_string = @iconv($in_charset, $out_charset.'//TRANSLIT', $string)) { switch ($out_charset) { case 'ISO-8859-1': $converted_string = rtrim($converted_string, "\x00"); break; } return $converted_string; } return $string; } static $ConversionFunctionList = array(); if (empty($ConversionFunctionList)) { $ConversionFunctionList['ISO-8859-1']['UTF-8'] = 'iconv_fallback_iso88591_utf8'; $ConversionFunctionList['ISO-8859-1']['UTF-16'] = 'iconv_fallback_iso88591_utf16'; $ConversionFunctionList['ISO-8859-1']['UTF-16BE'] = 'iconv_fallback_iso88591_utf16be'; $ConversionFunctionList['ISO-8859-1']['UTF-16LE'] = 'iconv_fallback_iso88591_utf16le'; $ConversionFunctionList['UTF-8']['ISO-8859-1'] = 'iconv_fallback_utf8_iso88591'; $ConversionFunctionList['UTF-8']['UTF-16'] = 'iconv_fallback_utf8_utf16'; $ConversionFunctionList['UTF-8']['UTF-16BE'] = 'iconv_fallback_utf8_utf16be'; $ConversionFunctionList['UTF-8']['UTF-16LE'] = 'iconv_fallback_utf8_utf16le'; $ConversionFunctionList['UTF-16']['ISO-8859-1'] = 'iconv_fallback_utf16_iso88591'; $ConversionFunctionList['UTF-16']['UTF-8'] = 'iconv_fallback_utf16_utf8'; $ConversionFunctionList['UTF-16LE']['ISO-8859-1'] = 'iconv_fallback_utf16le_iso88591'; $ConversionFunctionList['UTF-16LE']['UTF-8'] = 'iconv_fallback_utf16le_utf8'; $ConversionFunctionList['UTF-16BE']['ISO-8859-1'] = 'iconv_fallback_utf16be_iso88591'; $ConversionFunctionList['UTF-16BE']['UTF-8'] = 'iconv_fallback_utf16be_utf8'; } if (isset($ConversionFunctionList[strtoupper($in_charset)][strtoupper($out_charset)])) { $ConversionFunction = $ConversionFunctionList[strtoupper($in_charset)][strtoupper($out_charset)]; return self::$ConversionFunction($string); } throw new Exception('PHP does not has mb_convert_encoding() or iconv() support - cannot convert from '.$in_charset.' to '.$out_charset); } public static function recursiveMultiByteCharString2HTML($data, $charset='ISO-8859-1') { if (is_string($data)) { return self::MultiByteCharString2HTML($data, $charset); } elseif (is_array($data)) { $return_data = array(); foreach ($data as $key => $value) { $return_data[$key] = self::recursiveMultiByteCharString2HTML($value, $charset); } return $return_data; } return $data; } public static function MultiByteCharString2HTML($string, $charset='ISO-8859-1') { $string = (string) $string; $HTMLstring = ''; switch (strtolower($charset)) { case '1251': case '1252': case '866': case '932': case '936': case '950': case 'big5': case 'big5-hkscs': case 'cp1251': case 'cp1252': case 'cp866': case 'euc-jp': case 'eucjp': case 'gb2312': case 'ibm866': case 'iso-8859-1': case 'iso-8859-15': case 'iso8859-1': case 'iso8859-15': case 'koi8-r': case 'koi8-ru': case 'koi8r': case 'shift_jis': case 'sjis': case 'win-1251': case 'windows-1251': case 'windows-1252': $HTMLstring = htmlentities($string, ENT_COMPAT, $charset); break; case 'utf-8': $strlen = strlen($string); for ($i = 0; $i < $strlen; $i++) { $char_ord_val = ord($string[$i]); $charval = 0; if ($char_ord_val < 0x80) { $charval = $char_ord_val; } elseif ((($char_ord_val & 0xF0) >> 4) == 0x0F && $i+3 < $strlen) { $charval = (($char_ord_val & 0x07) << 18); $charval += ((ord($string[++$i]) & 0x3F) << 12); $charval += ((ord($string[++$i]) & 0x3F) << 6); $charval += (ord($string[++$i]) & 0x3F); } elseif ((($char_ord_val & 0xE0) >> 5) == 0x07 && $i+2 < $strlen) { $charval = (($char_ord_val & 0x0F) << 12); $charval += ((ord($string[++$i]) & 0x3F) << 6); $charval += (ord($string[++$i]) & 0x3F); } elseif ((($char_ord_val & 0xC0) >> 6) == 0x03 && $i+1 < $strlen) { $charval = (($char_ord_val & 0x1F) << 6); $charval += (ord($string[++$i]) & 0x3F); } if (($charval >= 32) && ($charval <= 127)) { $HTMLstring .= htmlentities(chr($charval)); } else { $HTMLstring .= '&#'.$charval.';'; } } break; case 'utf-16le': for ($i = 0; $i < strlen($string); $i += 2) { $charval = self::LittleEndian2Int(substr($string, $i, 2)); if (($charval >= 32) && ($charval <= 127)) { $HTMLstring .= chr($charval); } else { $HTMLstring .= '&#'.$charval.';'; } } break; case 'utf-16be': for ($i = 0; $i < strlen($string); $i += 2) { $charval = self::BigEndian2Int(substr($string, $i, 2)); if (($charval >= 32) && ($charval <= 127)) { $HTMLstring .= chr($charval); } else { $HTMLstring .= '&#'.$charval.';'; } } break; default: $HTMLstring = 'ERROR: Character set "'.$charset.'" not supported in MultiByteCharString2HTML()'; break; } return $HTMLstring; } public static function RGADnameLookup($namecode) { static $RGADname = array(); if (empty($RGADname)) { $RGADname[0] = 'not set'; $RGADname[1] = 'Track Gain Adjustment'; $RGADname[2] = 'Album Gain Adjustment'; } return (isset($RGADname[$namecode]) ? $RGADname[$namecode] : ''); } public static function RGADoriginatorLookup($originatorcode) { static $RGADoriginator = array(); if (empty($RGADoriginator)) { $RGADoriginator[0] = 'unspecified'; $RGADoriginator[1] = 'pre-set by artist/producer/mastering engineer'; $RGADoriginator[2] = 'set by user'; $RGADoriginator[3] = 'determined automatically'; } return (isset($RGADoriginator[$originatorcode]) ? $RGADoriginator[$originatorcode] : ''); } public static function RGADadjustmentLookup($rawadjustment, $signbit) { $adjustment = (float) $rawadjustment / 10; if ($signbit == 1) { $adjustment *= -1; } return $adjustment; } public static function RGADgainString($namecode, $originatorcode, $replaygain) { if ($replaygain < 0) { $signbit = '1'; } else { $signbit = '0'; } $storedreplaygain = intval(round($replaygain * 10)); $gainstring = str_pad(decbin($namecode), 3, '0', STR_PAD_LEFT); $gainstring .= str_pad(decbin($originatorcode), 3, '0', STR_PAD_LEFT); $gainstring .= $signbit; $gainstring .= str_pad(decbin($storedreplaygain), 9, '0', STR_PAD_LEFT); return $gainstring; } public static function RGADamplitude2dB($amplitude) { return 20 * log10($amplitude); } public static function GetDataImageSize($imgData, &$imageinfo=array()) { if (PHP_VERSION_ID >= 50400) { $GetDataImageSize = @getimagesizefromstring($imgData, $imageinfo); if ($GetDataImageSize === false || !isset($GetDataImageSize[0], $GetDataImageSize[1])) { return false; } $GetDataImageSize['height'] = $GetDataImageSize[0]; $GetDataImageSize['width'] = $GetDataImageSize[1]; return $GetDataImageSize; } static $tempdir = ''; if (empty($tempdir)) { if (function_exists('sys_get_temp_dir')) { $tempdir = sys_get_temp_dir(); } if (include_once(dirname(__FILE__).'/getid3.php')) { $getid3_temp = new getID3(); if ($getid3_temp_tempdir = $getid3_temp->tempdir) { $tempdir = $getid3_temp_tempdir; } unset($getid3_temp, $getid3_temp_tempdir); } } $GetDataImageSize = false; if ($tempfilename = tempnam($tempdir, 'gI3')) { if (is_writable($tempfilename) && is_file($tempfilename) && ($tmp = fopen($tempfilename, 'wb'))) { fwrite($tmp, $imgData); fclose($tmp); $GetDataImageSize = @getimagesize($tempfilename, $imageinfo); if (($GetDataImageSize === false) || !isset($GetDataImageSize[0]) || !isset($GetDataImageSize[1])) { return false; } $GetDataImageSize['height'] = $GetDataImageSize[0]; $GetDataImageSize['width'] = $GetDataImageSize[1]; } unlink($tempfilename); } return $GetDataImageSize; } public static function ImageExtFromMime($mime_type) { return str_replace(array('image/', 'x-', 'jpeg'), array('', '', 'jpg'), $mime_type); } public static function CopyTagsToComments(&$ThisFileInfo, $option_tags_html=true) { if (!empty($ThisFileInfo['tags'])) { $processLastTagTypes = array('id3v1','riff'); foreach ($processLastTagTypes as $processLastTagType) { if (isset($ThisFileInfo['tags'][$processLastTagType])) { $temp = $ThisFileInfo['tags'][$processLastTagType]; unset($ThisFileInfo['tags'][$processLastTagType]); $ThisFileInfo['tags'][$processLastTagType] = $temp; unset($temp); } } foreach ($ThisFileInfo['tags'] as $tagtype => $tagarray) { foreach ($tagarray as $tagname => $tagdata) { foreach ($tagdata as $key => $value) { if (!empty($value)) { if (empty($ThisFileInfo['comments'][$tagname])) { } elseif ($tagtype == 'id3v1') { $newvaluelength = strlen(trim($value)); foreach ($ThisFileInfo['comments'][$tagname] as $existingkey => $existingvalue) { $oldvaluelength = strlen(trim($existingvalue)); if (($newvaluelength <= $oldvaluelength) && (substr($existingvalue, 0, $newvaluelength) == trim($value))) { break 2; } if (function_exists('mb_convert_encoding')) { if (trim($value) == trim(substr(mb_convert_encoding($existingvalue, $ThisFileInfo['id3v1']['encoding'], $ThisFileInfo['encoding']), 0, 30))) { break 2; } } } } elseif (!is_array($value)) { $newvaluelength = strlen(trim($value)); $newvaluelengthMB = mb_strlen(trim($value)); foreach ($ThisFileInfo['comments'][$tagname] as $existingkey => $existingvalue) { $oldvaluelength = strlen(trim($existingvalue)); $oldvaluelengthMB = mb_strlen(trim($existingvalue)); if (($newvaluelengthMB == $oldvaluelengthMB) && ($existingvalue == getid3_lib::iconv_fallback('UTF-8', 'ASCII', $value))) { $ThisFileInfo['comments'][$tagname][$existingkey] = trim($value); break; } if ((strlen($existingvalue) > 10) && ($newvaluelength > $oldvaluelength) && (substr(trim($value), 0, strlen($existingvalue)) == $existingvalue)) { $ThisFileInfo['comments'][$tagname][$existingkey] = trim($value); break; } } } if (is_array($value) || empty($ThisFileInfo['comments'][$tagname]) || !in_array(trim($value), $ThisFileInfo['comments'][$tagname])) { $value = (is_string($value) ? trim($value) : $value); if (!is_int($key) && !ctype_digit($key)) { $ThisFileInfo['comments'][$tagname][$key] = $value; } else { if (!isset($ThisFileInfo['comments'][$tagname])) { $ThisFileInfo['comments'][$tagname] = array($value); } else { $ThisFileInfo['comments'][$tagname][] = $value; } } } } } } } if (!empty($ThisFileInfo['comments'])) { $StandardizeFieldNames = array( 'tracknumber' => 'track_number', 'track' => 'track_number', ); foreach ($StandardizeFieldNames as $badkey => $goodkey) { if (array_key_exists($badkey, $ThisFileInfo['comments']) && !array_key_exists($goodkey, $ThisFileInfo['comments'])) { $ThisFileInfo['comments'][$goodkey] = $ThisFileInfo['comments'][$badkey]; unset($ThisFileInfo['comments'][$badkey]); } } } if ($option_tags_html) { if (!empty($ThisFileInfo['comments'])) { foreach ($ThisFileInfo['comments'] as $field => $values) { if ($field == 'picture') { continue; } foreach ($values as $index => $value) { if (is_array($value)) { $ThisFileInfo['comments_html'][$field][$index] = $value; } else { $ThisFileInfo['comments_html'][$field][$index] = str_replace('&#0;', '', self::MultiByteCharString2HTML($value, $ThisFileInfo['encoding'])); } } } } } } return true; } public static function EmbeddedLookup($key, $begin, $end, $file, $name) { static $cache; if (isset($cache[$file][$name])) { return (isset($cache[$file][$name][$key]) ? $cache[$file][$name][$key] : ''); } $keylength = strlen($key); $line_count = $end - $begin - 7; $fp = fopen($file, 'r'); for ($i = 0; $i < ($begin + 3); $i++) { fgets($fp, 1024); } while (0 < $line_count--) { $line = ltrim(fgets($fp, 1024), "\t "); $explodedLine = explode("\t", $line, 2); $ThisKey = (isset($explodedLine[0]) ? $explodedLine[0] : ''); $ThisValue = (isset($explodedLine[1]) ? $explodedLine[1] : ''); $cache[$file][$name][$ThisKey] = trim($ThisValue); } fclose($fp); return (isset($cache[$file][$name][$key]) ? $cache[$file][$name][$key] : ''); } public static function IncludeDependency($filename, $sourcefile, $DieOnFailure=false) { global $GETID3_ERRORARRAY; if (file_exists($filename)) { if (include_once($filename)) { return true; } else { $diemessage = basename($sourcefile).' depends on '.$filename.', which has errors'; } } else { $diemessage = basename($sourcefile).' depends on '.$filename.', which is missing'; } if ($DieOnFailure) { throw new Exception($diemessage); } else { $GETID3_ERRORARRAY[] = $diemessage; } return false; } public static function trimNullByte($string) { return trim($string, "\x00"); } public static function getFileSizeSyscall($path) { $commandline = null; $filesize = false; if (GETID3_OS_ISWINDOWS) { if (class_exists('COM')) { $filesystem = new COM('Scripting.FileSystemObject'); $file = $filesystem->GetFile($path); $filesize = $file->Size(); unset($filesystem, $file); } else { $commandline = 'for %I in ('.escapeshellarg($path).') do @echo %~zI'; } } else { $commandline = 'ls -l '.escapeshellarg($path).' | awk \'{print $5}\''; } if (isset($commandline)) { $output = trim(`$commandline`); if (ctype_digit($output)) { $filesize = (float) $output; } } return $filesize; } public static function truepath($filename) { if (preg_match('#^(\\\\\\\\|//)[a-z0-9]#i', $filename, $matches)) { $goodpath = array(); foreach (explode('/', str_replace('\\', '/', $filename)) as $part) { if ($part == '.') { continue; } if ($part == '..') { if (count($goodpath)) { array_pop($goodpath); } else { return false; } } else { $goodpath[] = $part; } } return implode(DIRECTORY_SEPARATOR, $goodpath); } return realpath($filename); } public static function mb_basename($path, $suffix = '') { $splited = preg_split('#/#', rtrim($path, '/ ')); return substr(basename('X'.$splited[count($splited) - 1], $suffix), 1); } } 