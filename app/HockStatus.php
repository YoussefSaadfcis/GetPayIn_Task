<?php

namespace App;

enum HockStatus: string
{
    case NEW = 'new';
    case pending_order = 'pending_order';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case DUPLICATE = 'duplicate';
}
