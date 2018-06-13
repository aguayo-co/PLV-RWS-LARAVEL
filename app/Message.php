<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Cmgmyr\Messenger\Models\Message as BaseMessage;

class Message extends BaseMessage
{
    use DateSerializeFormat;
}
