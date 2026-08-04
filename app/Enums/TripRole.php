<?php

namespace App\Enums;

enum TripRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
