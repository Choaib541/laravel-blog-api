<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (isset(request()->search)) {
            $result =  Post::search(request()->search);
            if ($result->count() === 0) {
                return response($result, 404);
            };
            return $result;
        } else {
            return Post::with(["categories:name", "user:id,username,picture"])->paginate(9);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_own()
    {
        return Post::with("categories:name")->whereBelongsTo(auth()->user())->get();
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            "title" => ["required", "min:3"],
            "cover" => ["required"],
            "content" => ["required", "min:3"],
            "description" => ["required", "min:3"],
            "user_id" => ["required", "exists:users,id"],
            "tags" => ["required", 'regex:/^(\w+\|\w+)+$/i'],
        ]);
        $validated["cover"] = $request->file("cover")->store("posts_covers", "public");
        return  response(Post::create($validated), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $post = Post::find($id);

        $response = auth("sanctum")->user();

        return ["result" => $response->can("view", $post)];

        die();

        $post = Post::with(["categories:id,name", "user:id,username,picture"])->find($id);
        return $post;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            "title" => ["nullable", "min:3"],
            "description" => ["nullable", "min:3"],
            "content" => ["nullable", "min:3"],
            "user_id" => ["nullable", "exists:users,id"],
            "tags" => ["nullable", 'regex:/^(\w+\|\w+)+$/i'],
        ]);


        $post = Post::fund($id);
        $this->authorize("update", $post);

        if ($request->hasFile("cover")) {
            $validated["cover"] = $request->file("cover")->store("posts_covers", "public");
        }

        $post->update($validated);

        return  $post;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = Post::find($id);
        $this->authorize("delete", $post);
        File::delete(public_path("storage/" . $post->cover));
        return $post->delete();
    }
}
