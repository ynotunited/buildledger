<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()
            ->with('user:id,name,email')
            ->latest();

        if (! $request->user()?->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $clients = $query->paginate(20);

        return ClientResource::collection($clients);
    }

    public function store(StoreClientRequest $request)
    {
        $client = $request->user()->clients()->create($request->validated());

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Request $request, Client $client)
    {
        $this->ensureOwnedByUser($request, $client);

        return new ClientResource($client);
    }

    public function update(StoreClientRequest $request, Client $client)
    {
        $this->ensureOwnedByUser($request, $client);

        $client->update($request->validated());

        return new ClientResource($client);
    }

    public function destroy(Request $request, Client $client)
    {
        $this->ensureOwnedByUser($request, $client);

        $client->delete();

        return response()->json(['message' => 'Client deleted successfully']);
    }
}
