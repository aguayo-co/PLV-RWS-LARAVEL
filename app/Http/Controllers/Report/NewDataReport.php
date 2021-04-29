<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait NewDataReport
{
    protected function createQueryForNew(Request $request, $table, $column = 'id')
    {
        $query = DB::table($table);
        $this->setDateRanges($request, $table . '.created_at', $query);
        $query->addSelect(DB::raw("COUNT(DISTINCT {$table}.{$column}) as count"));
        return $query;
    }

    protected function newUsersWithPicturesQuery(Request $request)
    {
        return $this->createQueryForNew($request, 'users')
            ->join('cloud_files', function ($join) {
                $join->on('cloud_files.model_id', '=', 'users.id')
                    ->where('cloud_files.model_type', 'App\User')
                    ->where('cloud_files.attribute', 'picture')
                    ->whereNotNull('cloud_files.urls');
            });
    }

    protected function newMessagesQuery(Request $request, $private = 1)
    {
        return $this->createQueryForNew($request, 'messages')
            ->join('threads', function ($join) use ($private) {
                $join->on('threads.id', '=', 'messages.thread_id')
                    ->where('threads.private', $private);
            });
    }

    protected function getNewDataReport(Request $request, $type = 'all')
    {
        $data = collect();

        if ($type === 'all' || $type === 'newUsers') {
            $newUsers = $this->createQueryForNew($request, 'users');
            $data->put('newUsers', $newUsers->get());
        }

        if ($type === 'all' || $type === 'newUsersWithPicture') {
            $newUsersWithPicture = $this->newUsersWithPicturesQuery($request);
            $data->put('newUsersWithPicture', $newUsersWithPicture->get());
        }

        if ($type === 'all' || $type === 'newRatings') {
            $newRatings = $this->createQueryForNew($request, 'ratings', 'sale_id');
            $data->put('newRatings', $newRatings->get());
        }

        if ($type === 'all' || $type === 'newMessages') {
            $newMessages = $this->newMessagesQuery($request);
            $data->put('newMessages', $newMessages->get());
        }

        if ($type === 'all' || $type === 'newComments') {
            $newComments = $this->newMessagesQuery($request, 0);
            $data->put('newComments', $newComments->get());
        }

        return $data;
    }
}
