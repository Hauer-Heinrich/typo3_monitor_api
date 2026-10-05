<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

/**
 * ORIGINAL: https://github.com/steampixel/simplePHPRouter
 * MIT License
 *
 * Copyright (c) 2018 - 2020 SteamPixel and contributors
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

class FormatUtility {

    /**
     * Date formats allowed for the parameter "format" (GetLastSchedulerRun, GetLastExtensionListUpdate)
     */
    public const DATE_FORMATS = ['d M Y H:i:s', 'd M Y', 'H:i:s', 'c', 'r'];

    /**
     * formatDateTime
     * Was called by two operations but did not exist, so the parameter "format" caused a fatal error.
     *
     * @param int $timestamp
     * @param string $format one of self::DATE_FORMATS
     * @return string empty if the format is not allowed
     */
    public static function formatDateTime(int $timestamp, string $format): string {
        if (!in_array($format, self::DATE_FORMATS, true)) {
            return '';
        }

        return date($format, $timestamp);
    }

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
