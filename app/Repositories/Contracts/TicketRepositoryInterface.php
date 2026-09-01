<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;

interface TicketRepositoryInterface
{
    public function create(array $data): Ticket;

    public function update(Ticket $ticket, array $data): bool;

    public function getBoardedTicketsByUser(int $userId);
}