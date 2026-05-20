<?php

namespace App\Support;

final class P2pPolicy
{
    public const SOURCE_CHANNEL = 'p2p_policy';

    public const PURPOSE_KIND = 'p2p_policy';

    /** Mon–Fri (ISO weekday 1–5). */
    public const DEFAULT_WEEKDAYS_MASK = 31;

    public const LEG_MORNING = 'morning';

    public const LEG_AFTERNOON = 'afternoon';
}
