<?php

namespace App;

enum HoldStatus : string
{
    
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case COMPLETED = 'completed';
}
