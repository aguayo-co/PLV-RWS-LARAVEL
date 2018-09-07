<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Cmgmyr\Messenger\Models\Models;
use Cmgmyr\Messenger\Models\Thread as BaseThread;

class Thread extends BaseThread
{
    use DateSerializeFormat;

    protected $fillable = ['subject', 'product_id', 'private'];

    /**
     * Return a collection of usier_ids that are owners of this thread.
     *
     * For public threads, it is every participant from the conversation
     * plus the current logged user.
     * For private threads, it is only already existing participants.
     */
    protected function getOwnersIdsAttribute()
    {
        $owners = $this->participants->pluck('user_id');
        if (!$this->private && auth()->id()) {
            $owners->push(auth()->id());
        }
        return $owners->unique();
    }

    public function trashedParticipants()
    {
        return $this->participants()->onlyTrashed();
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }
}
