<?php

namespace App\Support;

class Roles
{
    const SUPERADMIN = 'superadmin';
    const ADMIN = 'admin';
    const DISPATCHER = 'dispatcher';
    const DEPARTMENT_HEAD = 'department_head';
    const INTERNAL_USER = 'internal_user';
    const DRIVER = 'driver';
    const ACCOUNTANT = 'accountant';

    const DISPATCH_WEB_ROLES = [
        self::ADMIN,
        self::DISPATCHER,
        self::DEPARTMENT_HEAD,
        self::INTERNAL_USER,
    ];

    const ALL_ROLES = [
        self::SUPERADMIN,
        self::ADMIN,
        self::DISPATCHER,
        self::DEPARTMENT_HEAD,
        self::INTERNAL_USER,
        self::DRIVER,
        self::ACCOUNTANT,
    ];
}
