<?php
declare(strict_types=1);

/**
 * ORIGINAL: https://github.com/steampixel/simplePHPRouter
 * MIT License
 *
 * Copyright (c) 2018 - 2020 SteamPixel and contributors
 *
 * Edited by www.hauer-heinrich.de
*/

namespace HauerHeinrich\Typo3MonitorApi\Utility;

class FormatUtility {

    /**
     * getHumanReadableSize
     *
     * @param float $bytes
     * @return string
     */
    public static function getHumanReadableSize(float $bytes): string {
        if ($bytes > 0) {
            $base = floor(log($bytes) / log(1024));
            $units = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"); //units of measurement

            return number_format(($bytes / pow(1024, floor($base))), 3) . " $units[$base]";
        }

        return "0 bytes";
    }
}
