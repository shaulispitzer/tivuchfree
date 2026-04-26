<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 *  Yemot API docs
 *  https://f2.freeivr.co.il/topic/55/api-%D7%92%D7%99%D7%A9%D7%AA-%D7%9E%D7%A4%D7%AA%D7%97%D7%99%D7%9D-%D7%9C%D7%9E%D7%A2%D7%A8%D7%9B%D7%95%D7%AA/15?_=1695834548787
 */
class YemotApi
{
    public const BASE_URL = 'https://www.call2all.co.il/ym/api/';

    public function getIvrNumber(): string
    {
        return config('services.yemot.ivr_number') ?? '';
    }

    public function getIvrPassword(): string
    {
        return config('services.yemot.ivr_password') ?? '';
    }

    public function getToken(): string
    {
        $ivrNumber = $this->getIvrNumber();
        $ivrPassword = $this->getIvrPassword();

        return "{$ivrNumber}:{$ivrPassword}";
    }

    public function deleteFile(string $path): Response
    {
        $data = [
            'token' => $this->getToken(),
            'action' => 'delete',
            'what' => "ivr2:{$path}",
        ];

        return Http::post(self::BASE_URL.'FileAction', $data);
    }

    public function sendNotifications(array $phones): ?Response
    {
        if (empty($phones)) {
            return null;
        }

        $data = [
            'token' => $this->getToken(),
            'templateId' => 25630, // example template ID
            'phones' => Arr::join($phones, ':'),
            'callerId' => $this->getIvrNumber(),
        ];

        return Http::post(self::BASE_URL.'RunCampaign', $data);
    }
}
