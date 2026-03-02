<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'Admin';
    case SALESPERSON = 'Salesperson';
    case LEADER = 'Leader';
    case LOAN_OFFICER = 'Loan Officer';
}
