<?php

namespace App\Http\Controllers\Api\Contact;

use App\Models\Contact\Call;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Call\Call as CallResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Call\CreateCall;
use App\Services\Contact\Call\UpdateCall;
use App\Services\Contact\Call\DestroyCall;
use App\Exceptions\MissingParameterException;

class ApiCallController extends ApiController
{
    /**
     * Get the list of calls.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $calls = auth()->user()->account->calls()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return CallResource::collection($calls)->additional(['meta' => [
            'statistics' => auth()->user()->account->getYearlyCallStatistics(),
        ]]);
    }

    /**
     * Get the detail of a given call.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CallResource($call);
    }

    /**
     * Store the call.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $call = (new CreateCall)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new CallResource($call);
    }

    /**
     * Update a call.
     *
     * @param  Request $request
     * @param  int $callId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $callId)
    {
        try {
            $call = (new UpdateCall)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                    'call_id' => $callId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new CallResource($call);
    }

    /**
     * Delete a call.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $callId)
    {
        try {
            (new DestroyCall)->execute([
                'account_id' => auth()->user()->account->id,
                'call_id' => $callId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int)$callId);
    }

    /**
     * Get the list of calls for a given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function calls(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $calls = $contact->calls()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

        return CallResource::collection($calls)->additional(['meta' => [
            'statistics' => auth()->user()->account->getYearlyCallStatistics(),
        ]]);
    }
}
