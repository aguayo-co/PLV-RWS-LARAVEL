<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Cmgmyr\Messenger\Models\Models;
use Cmgmyr\Messenger\Models\Participant as BaseParticipant;

class Participant extends BaseParticipant
{
    use DateSerializeFormat;

    public function user()
    {
        return $this->belongsTo(Models::user(), 'user_id')->withTrashed();
    }
}
