<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Constants\TicketStatus;
class TicketRepository implements TicketRepositoryInterface
{
    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }
        public function update(Ticket $ticket, array $data): bool
    {
        return $ticket->update($data);
    }
    public function getBoardedTicketsByUser(int $userId)
    {
        return Ticket::where('boarded_by', $userId)
            ->where('status', TicketStatus::BOARDED)
            ->with([
                'passenger:id,full_name,birthday', 
                'seat:id,seat_name',
                'boardedBy:id,name',
                'tripReservation.trip.departureCity:id,name',
                'tripReservation.trip.destinationCity:id,name'
            ])
            ->withoutGlobalScopes() 
            ->latest('boarded_at')
            ->get();
    }
}