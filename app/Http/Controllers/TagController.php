<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::where('workspace_id', $request->user()->current_workspace_id)
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'nullable|string|max:7',
        ]);

        $tag = Tag::create([
            'workspace_id' => $request->user()->current_workspace_id,
            'name'         => $validated['name'],
            'color'        => $validated['color'] ?? '#6366f1',
        ]);

        return response()->json($tag, 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:50',
            'color' => 'sometimes|string|max:7',
        ]);

        $tag->update($validated);

        return response()->json($tag);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json(['message' => 'Tag deleted']);
    }
}
