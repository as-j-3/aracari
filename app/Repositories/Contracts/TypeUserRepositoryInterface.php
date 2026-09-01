<?php

namespace App\Repositories\Contracts;

use App\Models\TypeOfUser;


interface TypeUserRepositoryInterface
{

    public function findByCode(string $code): ?TypeOfUser;

 
    public function getCustomerType(): ?TypeOfUser;


    public function getProviderType(): ?TypeOfUser;


    public function findById(int $id): ?TypeOfUser;

  
    public function all(): \Illuminate\Database\Eloquent\Collection;


    public function create(array $data): ?TypeOfUser;


    public function update(int $id, array $data): ?TypeOfUser;


    public function delete(int $id): bool;
}
