<?php

namespace App\Repositories;

use App\Models\Passenger;
use App\Repositories\Contracts\PassengerRepositoryInterface;

class PassengerRepository implements PassengerRepositoryInterface
{
    public function findOrCreate(array $data): Passenger
    {
        return Passenger::firstOrCreate(
            [
                'created_by' => $data['created_by'],
                'full_name' => $data['full_name'],
                'birthday' => $data['birthday']
            ],
            $data
        );
    }
}