<?php

namespace App\Helpers;

/**
 * MySocial Smart ID Generator
 * Logic: PREFIX + DATE(YYMMDD) + RAND(2) + SERIAL + RAND(1) + SECURITY(Z+4)
 */
class IdGeneratorHelper
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Final Smart ID Generator Logic
     */
    public function generate($type, $parentSerial)
    {
        // 1. Prefix Selection
        $prefix = $this->getPrefix($type);

        // 2. Date Component (YYMMDD) - 260305
        $datePart = date('ymd');

        // 3. Random Character Pool
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        // Random Mix: 2 characters before serial, 1 after serial
        $randMid = substr(str_shuffle($chars), 0, 2);
        $randEnd = substr(str_shuffle($chars), 0, 1);

        // 4. Security Code (Hamesha Z se shuru, total 5 chars)
        $security = 'Z' . substr(str_shuffle($chars), 0, 4);

        /**
         * Final Pattern Assembly (No Dashes)
         * Pattern: PREFIX + DATE + RAND1 + SERIAL + RAND2 + SECURITY
         */
        $finalId = $prefix . $datePart . $randMid . $parentSerial . $randEnd . $security;

        return strtoupper($finalId);
    }

    /**
     * Separate Logic for each Type
     */
    private function getPrefix($type)
    {
        switch (strtolower($type)) {
            case 'user':    return 'USR';
            case 'channel': return 'CH';
            case 'video':   return 'VID';
            case 'reel':    return 'REL';
            case 'post':    return 'PST';
            case 'appeal':  return 'APL';
            case 'story':   return 'STR';
            default:        return 'MSC'; // MySocial Common
        }
    }
}
