<?php

namespace App\Observers;

use App\Models\ProviderBranch;
use App\Models\Service;

class ProviderBranchObserver
{

    public function created(ProviderBranch $branch): void
    {
        Service::create([
            'branch_id' => $branch->id,
        ]);
    }


    public function forceDeleted(ProviderBranch $branch): void
    {
        Service::where('branch_id', $branch->id)->forceDelete();
    }
}
