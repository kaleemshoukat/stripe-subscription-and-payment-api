<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static ACTIVE()
 * @method static static DISABLED()
 */
final class SubscriptionStatus extends Enum
{
    const ACTIVE = 1;
    const DISABLED = 0;     //disable also includes cancelled subscriptions
}
