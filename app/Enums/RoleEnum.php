<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'Admin';
    case USER = 'User';
    case LEADER = 'Leader';
    case LOAN_OFFICER = 'Loan Officer';
}
