<?php
namespace App\Http\Controllers;
use App\Services\CareerOpportunitySearch;
use Illuminate\Http\Request;
class CareerExplorerController extends Controller {
    public function index(Request $request, CareerOpportunitySearch $search) { return view('career-explorer', $search->search($request->query())); }
    public function api(Request $request, CareerOpportunitySearch $search) { return response()->json($search->search($request->query())); }
}
