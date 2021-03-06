<?php namespace App\Http\Controllers;

use App\MarkdownExtraParser;
use App\MarkDownHelper;
use App\Post;
use App\Services\SchedulerAls;
use App\Tag;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use App\TagsHelper;

class PostsController extends BaseController
{

    use MarkDownHelper, TagsHelper;

    public function __construct(MarkdownExtraParser $mk, SchedulerAls $scheduler)
    {
        parent::__construct($mk, $scheduler);
        $this->middleware('auth', ['except' => ['index', 'show', 'search']]);
    }

    public function search()
    {
        $input = Input::get('search');

        $posts = (new Post())->search($input);

        return Response::json(['data' => $posts->toArray(), 'status' => 'success', 'message' => "Post Search"], 200);
    }

    public function index()
    {
        //Blade::setContentTags('<%', '%>');        // for variables and all things Blade
        //Blade::setEscapedContentTags('<%%', '%%>');   // for escaped data

        if (Auth::user()) {
            $posts = Post::OrderByCreatedAt()->get();
        } else {
            $posts = Cache::rememberForever('posts', function () {
                return Post::Published()->OrderByCreatedAt()->get();
            });
        }

        $posts->load('tags');

        if (!Request::isJson()) {
            return View::make('posts.index', compact('posts'));
        } else {
            return Response::json(
                [
                    'data' => $posts->toArray(),
                    'status' => 'success',
                    'message' => "Post Index"
                ],
                200
            );
        }
    }

    /**
     * Show the form for creating a new post
     *
     * @return Response
     */
    public function create()
    {
        return View::make('posts.create');
    }

    /**
     * Store a newly created post in storage.
     *
     * @return Response
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $validator = Validator::make($data = Input::all(), Post::$rules);
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }
        //TODO REMOVE name field after imports

        $data['name'] = "Not needed";
        $data['rendered_body'] = $this->getMarkdownTool()->defaultTransform($data['body']);
        $data['scheduled'] = new \Carbon\Carbon();

        $post = Post::create($data);

        $date = $post->created_at;

        $this->handleTags($post, $request);

        if (Request::format() == 'html') {
            return Redirect::route('posts.index');
        } else {
            return Response::json(
                [
                    'data' => $post->toArray(),
                    'status' => 'success',
                    'message' => "Post Created"
                ],
                200
            );
        }
    }

    /**
     * Display the specified post.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $post = Cache::rememberForever('post_' . $id, function () use ($id) {
            return Post::findOrFail($id);
        });

        $posts = Cache::rememberForever('posts_sidebar', function () {
            return Post::all()->sortBy("created_at", null, true);
        });

        $active = $post->id;

        return View::make('posts.show', compact('post', 'posts', 'active'));
    }

    /**
     * Show the form for editing the specified post.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $post = Post::find($id)->load('tags');
        $t = [];
        foreach ($post->tags as $tag) {
            $t[] = $tag->name;
        }
        $tags_string = implode(',', $t);
        return View::make('posts.edit', compact('post', 'tags_string'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(\Illuminate\Http\Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $validator = Validator::make($data = Input::all(), Post::$rules);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }
        $data['rendered_body'] = $this->getMarkdownTool()->defaultTransform($data['body']);
        $post->update($data);
        $post->tags()->detach();

        $this->handleTags($post, $request);

        return Redirect::route('posts.show', $post->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        Post::destroy($id);

        return Redirect::route('posts.index');
    }
}
