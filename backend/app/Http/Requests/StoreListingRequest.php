<?php

namespace App\Http\Requests;

use App\Models\Media;
use App\Models\Product;
use Closure;
use Illuminate\Support\Facades\DB;

class StoreListingRequest extends ListingRequest
{
    protected function policyAbility(): string
    {
        return 'create';
    }
}
