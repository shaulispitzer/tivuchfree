<?php

namespace App\Enums;

/** @typescript */
enum Neighbourhood: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case Sanhedria = 'Sanhedria';
    case SanhedriaMurchavet = 'Sanhedria Murchavet';
    case BarIlan = 'Bar Ilan';
    case Gush80 = 'Gush 80';
    case Belz = 'Belz';
    case Romema = 'Romema';
    case Sorotzkin = 'Sorotzkin';
    case MekorBaruch = 'Mekor Baruch';
    case Geula = 'Geula';
    case GivatShaul = 'Givat Shaul';
    case HarNof = 'Har Nof';

    public function label(): string
    {
        return $this->value;
    }
}
