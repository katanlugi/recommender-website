<?php

namespace App\Http\Controllers;

use App\User;
use App\Rating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GuestController extends Controller
{
    protected static $lowestGuestId = 2000000;

    public static function getGuestIdFromSession()
    {
        Log::info('getGuestIdFromSession guestId: '.Session::get('guestId'));
        
        if (!Session::has('guestId')){
            GuestController::initGuestUser();
        }
        return Session::get('guestId');
    }

    public static function resetGuestId()
    {
        Log::info('resetting guest ID');
        GuestController::flushUnusedGuestIds();
        GuestController::initGuestUser();
        // $newId = GuestController::initGuestUser();
        // return $user;
    }

    public function restoreSession(Request $req, $sessionId)
    {
        Log::info('Restoring session from sessionId '.$sessionId);
        Session::setId($sessionId);
        Session::start();
        return Session::get('guestId');
    }

    public function getSessionId(Request $req)
    {
        return Session::getId();
        return $req->session()->getId();
    }

    private static function initGuestUser()
    {
        $newId = GuestController::generateNewGuestId();
        
        $user = new User();
        $user->extendFillableId();
        $user->id = $newId;
        $user->save();

        Session::put('guestId', $user->id);
        // return $user;
    }

    private static function flushUnusedGuestIds()
    {
        Log::info('Flushing unused guest ID');
        $guests = User::where('id', '>=', GuestController::$lowestGuestId)
            ->whereDate('created_at', '<', Carbon::yesterday())
            ->get();
        $counter = 0;
        foreach ($guests as $guest) {
            $nbRatings = Rating::where('user_id', $guest->id)->count();
            if($nbRatings == 0) {
                Log::info('deleting unused guestId: '.$guest->id);
                $guest->delete();
                $counter++;
            }
        }
        Log::info('Deleted '.$counter.' unused guestIds');
    }

    /**
     * Generates a new userId for guest user that is assured 
     * to be bigger than the previous guestId.
     *
     * @return void
     */
    private static function generateNewGuestId()
    {
        $lastGuest = User::latest()
            ->where('id', '>=', GuestController::$lowestGuestId)
            ->first();
        if(isset($lastGuest)){
            return GuestController::getNextAvailableGuestId($lastGuest->id);
        }
        
        return GuestController::$lowestGuestId;
    }

    /**
     * Searches for the next available free userId starting from the given id
     *
     * @param int $id       userId to start the search from
     * @return int $newId   the next free guest id
     */
    private static function getNextAvailableGuestId(int $id)
    {
        if (User::where('id', $id)->count() > 0) {
            $id = GuestController::getNextAvailableGuestId(++$id);
        }
        return $id;
    }
}
