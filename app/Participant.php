<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Cmgmyr\Messenger\Models\Participant as BaseParticipant;

class Participant extends BaseParticipant
{
    use DateSerializeFormat;
}
