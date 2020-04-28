<?php
/**
 * HELOstore
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * https://helostore.com/ and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Add-ons
 * @package    HELOstore
 * @copyright  Copyright (c) 2015-2016 HELOstore. (https://helostore.com/)
 * @license    https://helostore.com/legal/license-agreement/   License Agreement
 * @version    $Id$
 */

namespace HeloStore\hCaptcha;

use Tygh\Http;

/**
 * Class License
 *
 * @package HeloStore\ADLS
 */
class hCaptchaAPI
{
    const DIFFICULTY_EASY = 1;
    const DIFFICULTY_MODERATE = 2;
    const DIFFICULTY_DIFFICULT = 3;
    const DIFFICULTY_ALWAYS_ON = 4;

    const INTEREST_SHOPPING = 9;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * hCaptchaAPI constructor.
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param $hostNames
     * @param int $difficulty
     * @param array $interests
     * @return mixed|null
     * @throws \Exception
     */
    public function addNewSite($hostNames, $difficulty = self::DIFFICULTY_MODERATE, $interests = array(self::INTEREST_SHOPPING))
    {
        if (defined('HCAPTCHA_SITE_KEY_SINK')) {
            return HCAPTCHA_SITE_KEY_SINK;
        }
        if (!is_array($hostNames)) {
            $hostNames = [$hostNames];
        }
        $url = "https://accounts.hcaptcha.com/dashboard/sitekey";
        
        $data = array(
            'name' => $hostNames[0],
            'hostnames' => $hostNames,
            'difficulty' => $difficulty,
            'interests' => $interests,
        );
        $payload = json_encode($data);
        ws_log_file(['$url' => $url, '$payload' => $payload]);
        $extra = array(
            'headers' => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->apiKey),
            ),
        );
        $siteKey = null;
        try {

            $responseText = Http::post($url, $payload, $extra);
            $responseJson = json_decode($responseText, true);
            if (!empty($responseJson)) {
                if (!empty($responseJson['error'])) {

                    throw new \Exception($responseJson['error']);
                }
                if (!empty($responseJson['sitekey'])) {
                    $siteKey = $responseJson;
                }

            }
        } catch (\Exception $exception) {
            $err = sprintf('Unable to produce new site key, please contact HELOstore. (error: %s)', $exception->getMessage());
            throw new \Exception($err);
        }

        return $siteKey;
    }
}
