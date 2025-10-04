<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TezaursController extends Controller
{
    public function lookup(Request $request)
    {
        $word = $request->query('word');
        if (!$word) {
            return response()->json(['error' => 'No word provided'], 400);
        }
        $response = Http::get("http://api.tezaurs.lv:8182/api/word/definitions?term=" . urlencode($word));
        if ($response->successful()) {
            return response()->json($response->json());
        }
        return response()->json(['error' => 'Failed to fetch data'], 500);
    }
}
