<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jubaer\Zoom\Facades\Zoom;
use Carbon\Carbon;

class meetingController extends Controller
{
    public function createMeeting(Request $request) {
        try {
            $meetings = Zoom::createMeeting([
                "agenda" => 'your agenda',
                "topic" => 'your topic',
                "type" => 2, // 1 => instant, 2 => scheduled, 3 => recurring with no fixed time, 8 => recurring with fixed time
                "duration" => 60, // in minutes
                "timezone" => 'Africa/Lusaka', // set your t imezone
                "password" => '123456',
                "start_time" => Carbon::now()->format('m-d-Y H:i:j'), // set your start time
                "template_id" => 'set your template id', // set your template id  Ex: "Dv4YdINdTk+Z5RToadh5ug==" from https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetingtemplates
                "pre_schedule" => false,  // set true if you want to create a pre-scheduled meeting
                "schedule_for" => 'mainja947@gmail.com', // set your schedule for
                "settings" => [
                    'join_before_host' => false, // if you want to join before host set true otherwise set false
                    'host_video' => false, // if you want to start video when host join set true otherwise set false
                    'participant_video' => false, // if you want to start video when participants join set true otherwise set false
                    'mute_upon_entry' => false, // if you want to mute participants when they join the meeting set true otherwise set false
                    'waiting_room' => false, // if you want to use waiting room for participants set true otherwise set false
                    'audio' => 'both', // values are 'both', 'telephony', 'voip'. default is both.
                    'auto_recording' => 'none', // values are 'none', 'local', 'cloud'. default is none.
                    'approval_type' => 0, // 0 => Automatically Approve, 1 => Manually Approve, 2 => No Registration Required
                ],
            ]);

            return response()->json([
                'message' => 'Meeting created successfully!',
                'meeting' => $meetings
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getAllMeetings(Request $request) {
        try {
            $meetings = Zoom::getAllMeeting();

            return response()->json([
                'data' => $meetings
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getMeeting(Request $request) {
        try {
            $meetings = Zoom::getMeeting($request->meeting_id);

            return response()->json([
                'data' => $meetings
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function endMeeting(Request $request) {
        try {
            $meetings = Zoom::endMeeting($request->meeting_id);

            return response()->json([
                'message' => 'You have ended the meeting'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deleteMeeting(Request $request) {
        try {
            $meetings = Zoom::deleteMeeting($request->meeting_id);

            return response()->json([
                'message' => 'You have deleted the meeting'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getUsers(Request $request) {
        try {
            $users = Zoom::getUsers(['status' => 'active']);

            return response()->json([
                'data' => $users
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }
}