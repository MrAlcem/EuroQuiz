<?php

namespace App;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
