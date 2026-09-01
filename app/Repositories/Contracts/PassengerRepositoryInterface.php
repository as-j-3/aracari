<?php

namespace App\Repositories\Contracts;

use App\Models\Passenger;

interface PassengerRepositoryInterface
{
    public function findOrCreate(array $data): Passenger;
}