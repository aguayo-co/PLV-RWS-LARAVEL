<?php

namespace App;

use App\Traits\HasStatuses;
use App\Traits\SaveLater;
use Cmgmyr\Messenger\Models\Thread as BaseThread;
use Illuminate\Support\Facades\Storage;

class Thread extends BaseThread
{
    protected $with = ['messages', 'participants'];
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

    public function product()
    {
        return $this->belongsTo('App\Product');
    }
}
