<?php

namespace App\Traits;

trait HasStatusHistory
{
    public function setStatusAttribute($value)
    {
        $statusHistory = $this->status_history ?: [];

        $user = auth()->user();
        $statusHistory[$value] = [
            'date' => now(),
            'user_id' => $user ? $user->id : null,
        ];

        $this->attributes['status_history'] = json_encode($statusHistory);
        $this->attributes['status'] = $value;
    }

    public function getStatusHistoryAttribute($value)
    {
        return json_decode($value, true);
    }
}
