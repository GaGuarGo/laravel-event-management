<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Traits\CanLoadRelationships;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{

    use CanLoadRelationships;

    private array $relations = ['user', 'attendees', 'attendees.user'];

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
        $this->middleware('throttle:api')->only(['store', 'destroy']);
        $this->authorizeResource(Event::class, 'event');
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //Processo de Serializing-> transformando em JSON

        $query = $this->loadRelationships(Event::query(), $this->relations);


        return EventResource::collection(
            $query->latest()->paginate()
        );
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        //Colocar Headers para retornar um JSON com as validações

        $event = Event::create(
            [...$request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
            ]),
                'user_id' => $request->user()->id,
            ]
        );

        return new EventResource($this->loadRelationships($event, $this->relations));
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        $event->load('user');
        $event->load('attendees');
        return new EventResource($this->loadRelationships($event, $this->relations));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
//        if (Gate::denies('update-event', $event)) {
//            abort(403, 'You are not authorized to update this event.');
//        }

//        $this->authorize("update-event", $event);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
        ]);

        $event->update($data);

        return new EventResource($this->loadRelationships($event, $this->relations));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully'], status: 204);
    }
}
