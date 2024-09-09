<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use WisdomDiala\Countrypkg\Models\Country;
use WisdomDiala\Countrypkg\Models\State;

class countryStateController extends Controller
{
    public function getAllCountries()
    {
        try {
            $countries = Country::all();

            return response()->json([
                'status' => 200,
                'data' => $countries
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function getAllStates()
    {
        try {
            $states = State::all();

            return response()->json([
                'status' => 200,
                'data' => $states
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    	
    }

    public function getCountryStates(Request $request)
    {
        try {
            $states = State::where('country_id', $request->country_id)->get();

            return response()->json([
                'status' => 200,
                'data' => $states
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    	
    }
}
