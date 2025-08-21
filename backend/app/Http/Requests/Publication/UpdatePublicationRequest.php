<?php

namespace App\Http\Requests\Publication;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\Response;

class UpdatePublicationRequest extends CreatePublicationRequest
{
    public function authorize(Gate $gate): Response
    {
        return $gate->inspect('update', $this->route('publication'));
    }
}
