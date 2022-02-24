<?php

namespace TrueCore\App\Libraries\Payment\Handlers\Consts;

/**
 * Class TinkoffOrderStatus
 *
 * @package TrueCore\App\Libraries\Payment\Handlers\Consts
 */
class TinkoffOrderStatus
{
    public const STATUS_NEW       = 'NEW';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_EXPIRED   = 'DEADLINE_EXPIRED';
    public const STATUS_REJECTED  = 'REJECTED';
}
