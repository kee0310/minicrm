<?php

namespace App;

enum RoleEnum: string
{
    case ADMIN = 'Admin';
    case USER = 'User';
    case LEADER = 'Leader';
}
