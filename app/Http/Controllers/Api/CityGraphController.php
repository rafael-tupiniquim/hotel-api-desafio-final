<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCityEdgeRequest;
use App\Http\Requests\StoreCityNodeRequest;
use App\Models\CityEdge;
use App\Models\CityNode;
use Illuminate\Http\JsonResponse;

/** Mapa da cidade (pontos e ruas) usado pelo Dijkstra. */
class CityGraphController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'nodes' => CityNode::all(),
            'edges' => CityEdge::all(),
        ]);
    }

    /** Cadastra um ponto do mapa (ex.: um cruzamento). */
    public function storeNode(StoreCityNodeRequest $request): JsonResponse
    {
        $node = CityNode::create($request->validated());

        return response()->json($node, 201);
    }

    /** Cadastra uma rua entre dois pontos; "distance_km" é o peso usado pelo Dijkstra. */
    public function storeEdge(StoreCityEdgeRequest $request): JsonResponse
    {
        $edge = CityEdge::create($request->validated());

        return response()->json($edge->load('fromNode', 'toNode'), 201);
    }
}
