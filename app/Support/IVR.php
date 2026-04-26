<?php

namespace App\Support;

use App\Models\Property;
use Illuminate\Support\Collection;

/**
 *  Yemot API Module docs
 *  https://f2.freeivr.co.il/topic/56/%D7%9E%D7%95%D7%93%D7%95%D7%9C-api-%D7%AA%D7%A7%D7%A9%D7%95%D7%A8-%D7%A2%D7%9D-%D7%9E%D7%97%D7%A9%D7%91%D7%99%D7%9D-%D7%95%D7%9E%D7%9E%D7%A9%D7%A7%D7%99-%D7%A0%D7%AA%D7%95%D7%A0%D7%99%D7%9D-%D7%97%D7%99%D7%A6%D7%95%D7%A0%D7%99%D7%99%D7%9D
 */
class Ivr
{
    /**
     * Yemot read block (part 2): first field = query param that carries the key press.
     */
    public const READ_MENU_PARAM = 'ivr_menu';

    /**
     * Property used for the "press 2 for street" demo in the IVR.
     */
    public const PROPERTY_ID_STREET_DEMO = 44;

    /**
     * Menu key: hear street for {@see PROPERTY_ID_STREET_DEMO}.
     */
    public const MENU_KEY_STREET = '2';

    // actions
    public const LIST_PROPERTIES = 1;

    public static array $extensions = [
        'menu' => '2', // main menu
        'recordings' => '8', // audio files
    ];

    public static array $recordings = [
        'messages' => '3',
        'properties' => '4',
    ];

    public static array $messages = [
        'welcome' => '001',
        'thanks' => '067',
        'file_deleted' => '082',
        'error' => '083',
    ];

    public static function getMessagePath(string $message): string
    {
        return '/'.self::$extensions['recordings'].'/'.self::$recordings['messages'].'/'.self::$messages[$message];
    }

    public static function propertyNamesPath(): string
    {
        return '/'.self::$extensions['recordings'].'/'.self::$recordings['properties'];
    }

    public static function propertyName(Property|int $propertyId): string
    {
        if ($propertyId instanceof Property) {
            $propertyId = $propertyId->id;
        }

        return self::propertyNamesPath().'/'.$propertyId;
    }

    /**
     * A period-separated list of file-paths
     * output: f-/8/1/1028.f-/8/3/004.f-/8/2/2005
     */
    public static function renderFilesList(Collection $files): string
    {
        return $files->map(fn ($file) => "f-{$file}")->join('.');
    }

    /**
     * Play the welcome file, then read a single DTMF digit. The digit is sent back as
     * {@see READ_MENU_PARAM} (e.g. {@see MENU_KEY_STREET} for street demo).
     *
     * @see https://f2.freeivr.co.il/post/76
     */
    public static function readWelcomeThenMenuDigit(): string
    {
        $file = 'f-'.self::getMessagePath('welcome');

        return 'read='.$file.'='.self::READ_MENU_PARAM.',yes,1,1,7,Number,yes,no,';
    }

    /**
     * TTS (Text) in Yemot: characters `.` `-` are forbidden; strip them to avoid API errors.
     */
    public static function sanitizeTtsText(string $text): string
    {
        $text = str_replace(['.', ',', '-', '\'', "\u{2013}", "\u{2014}", '"', "\u{05F4}"], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Response body: play one TTS string (Hebrew).
     */
    public static function idListMessageText(string $text): string
    {
        return 'id_list_message=t-'.self::sanitizeTtsText($text);
    }
}
